<?php

namespace App\Filament\Acv\Pages;

use App\Enums\UserRole;
use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Models\AcvCase;
use App\Models\CaseFollowup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FollowupCases extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.case-list';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Seguimiento 90 días';

    protected static ?string $title = 'Seguimiento a los 90 días';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'seguimiento';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::Administrator,
            UserRole::Leader,
            UserRole::Followup,
        ], true);
    }

    public function table(Table $table): Table
    {
        $perPage = auth()->user()?->recordsPerPage() ?? 50;

        return $table
            ->query($this->followupQuery())
            ->columns([
                TextColumn::make('case_number')
                    ->label('Caso')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->description(fn (AcvCase $record): string => $record->patient->identification)
                    ->searchable(['full_name', 'identification'])
                    ->sortable(),
                TextColumn::make('discharged_at')
                    ->label('Egreso')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('days_elapsed')
                    ->label('Días')
                    ->state(fn (AcvCase $record): int => (int) $record->discharged_at->diffInDays(now()))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state < 90  => 'primary',
                        $state <= 100 => 'success',
                        $state <= 115 => 'warning',
                        default       => 'danger',
                    })
                    ->tooltip('Días transcurridos desde el egreso'),
                TextColumn::make('eapb')->label('EAPB')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('followup_status')
                    ->label('Estado seguimiento')
                    ->state(fn (AcvCase $record): string => match (true) {
                        $record->followup?->is_auto_closed     => 'Sin contacto (auto)',
                        $record->followup?->is_effective       => 'Efectivo',
                        $record->followup !== null             => 'No efectivo',
                        default                                => 'Pendiente',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Efectivo'           => 'success',
                        'No efectivo'        => 'danger',
                        'Sin contacto (auto)' => 'gray',
                        default              => 'warning',
                    }),
                TextColumn::make('followup.rankin_90_days')
                    ->label('Rankin 90d')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('followup.contacted_at')
                    ->label('Contacto')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('followup.user.name')
                    ->label('Registrado por')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('followup_state')
                    ->label('Estado')
                    ->options([
                        'pending'    => 'Pendiente',
                        'effective'  => 'Efectivo',
                        'ineffective' => 'No efectivo',
                        'auto'       => 'Sin contacto (auto)',
                        'all'        => 'Todos (incluye historial)',
                    ])
                    ->default('pending')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'pending') {
                            'effective'   => $query->whereHas('followup', fn ($q) => $q->where('is_effective', true)),
                            'ineffective' => $query->whereHas('followup', fn ($q) => $q->where('is_effective', false)->where('is_auto_closed', false)),
                            'auto'        => $query->whereHas('followup', fn ($q) => $q->where('is_auto_closed', true)),
                            'all'         => $query,
                            default       => $query->doesntHave('followup'),
                        };
                    }),
            ])
            ->recordActions([
                Action::make('register_followup')
                    ->label(fn (AcvCase $record): string => $record->followup ? 'Actualizar' : 'Registrar')
                    ->icon(fn (AcvCase $record): string => $record->followup ? 'heroicon-m-pencil-square' : 'heroicon-m-phone')
                    ->color(fn (AcvCase $record): string => $record->followup?->is_effective ? 'success' : 'primary')
                    ->visible(fn (AcvCase $record): bool => ! ($record->followup?->is_auto_closed ?? false))
                    ->modalHeading(fn (AcvCase $record): string => "Seguimiento — {$record->case_number} · {$record->patient->full_name}")
                    ->modalWidth('lg')
                    ->form([
                        DateTimePicker::make('contacted_at')
                            ->label('Fecha y hora del contacto')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Toggle::make('is_effective')
                            ->label('¿Se logró contacto efectivo con el paciente?')
                            ->live()
                            ->default(false),
                        Select::make('rankin_90_days')
                            ->label('Escala de Rankin modificada a los 90 días')
                            ->options([
                                0 => '0 — Sin síntomas',
                                1 => '1 — Sin discapacidad significativa',
                                2 => '2 — Discapacidad leve',
                                3 => '3 — Discapacidad moderada',
                                4 => '4 — Discapacidad moderada-severa',
                                5 => '5 — Discapacidad severa',
                                6 => '6 — Muerte',
                            ])
                            ->visible(fn (Get $get): bool => (bool) $get('is_effective'))
                            ->required(fn (Get $get): bool => (bool) $get('is_effective'))
                            ->native(false),
                        Textarea::make('observations')
                            ->label('Observaciones')
                            ->rows(3)
                            ->placeholder('Resultado del contacto, estado referido por el paciente o familiar, etc.'),
                    ])
                    ->fillForm(fn (AcvCase $record): array => [
                        'contacted_at'   => $record->followup?->contacted_at ?? now(),
                        'is_effective'   => $record->followup?->is_effective ?? false,
                        'rankin_90_days' => $record->followup?->rankin_90_days,
                        'observations'   => $record->followup?->observations,
                    ])
                    ->action(function (AcvCase $record, array $data): void {
                        CaseFollowup::updateOrCreate(
                            ['acv_case_id' => $record->id],
                            [
                                'user_id'        => auth()->id(),
                                'contacted_at'   => $data['contacted_at'],
                                'is_effective'   => $data['is_effective'],
                                'rankin_90_days' => $data['is_effective'] ? ($data['rankin_90_days'] ?? null) : null,
                                'observations'   => $data['observations'] ?? null,
                                'is_auto_closed' => false,
                            ],
                        );

                        Notification::make()
                            ->title('Seguimiento registrado correctamente')
                            ->success()
                            ->send();
                    }),

                Action::make('view_case')
                    ->label('Ver caso')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (AcvCase $record): string => AcvCaseResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (): bool => auth()->user()?->canManageAllCases() ?? false),
            ])
            ->defaultSort('discharged_at', 'asc')
            ->striped()
            ->searchDebounce('400ms')
            ->paginationPageOptions([$perPage])
            ->defaultPaginationPageOption($perPage)
            ->emptyStateHeading('Sin casos en ventana de seguimiento')
            ->emptyStateDescription('Los casos aparecen aquí entre los días 87 y 120 después del egreso.');
    }

    private function followupQuery(): Builder
    {
        return AcvCase::query()
            ->with(['patient', 'followup.user'])
            ->whereNotNull('discharged_at')
            ->where('discharged_at', '<=', now()->subDays(87))
            ->where('is_cancelled', false)
            ->where(fn (Builder $q) => $q->whereNull('deceased')->orWhere('deceased', false))
            ->where(fn (Builder $q) => $q->whereNull('discharge_destination')->orWhere('discharge_destination', '!=', 'Remitido'))
            ->where(fn (Builder $q) => $q->whereNull('stroke_type')->orWhere('stroke_type', '!=', 'Imitador del Ictus'));
    }
}
