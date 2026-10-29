<?php

namespace App\Filament\InfusionMonitoring\Resources\PumpDevices;

use App\Enums\ProgramPermission;
use App\Filament\InfusionMonitoring\Resources\PumpDevices\Pages\CreatePumpDevice;
use App\Filament\InfusionMonitoring\Resources\PumpDevices\Pages\EditPumpDevice;
use App\Filament\InfusionMonitoring\Resources\PumpDevices\Pages\ListPumpDevices;
use App\Models\PumpDevice;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PumpDeviceResource extends Resource
{
    protected static ?string $model = PumpDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Bombas';

    protected static ?string $modelLabel = 'bomba';

    protected static ?string $pluralModelLabel = 'Bombas registradas';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ViewCases, 'BOMBAS');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::EditCases, 'BOMBAS');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::EditCases, 'BOMBAS');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación de la bomba')->columns(2)->schema([
                TextInput::make('hospital_device_id')->label('ID del dispositivo hospitalario')->required()->unique(ignoreRecord: true),
                TextInput::make('serial_number')->label('Número de serie'),
                TextInput::make('model')->label('Modelo'),
                TextInput::make('device_group')->label('Grupo de la empresa'),
                Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                Select::make('icu_unit_id')->label('Unidad')->relationship('icuUnit', 'name')->searchable()->preload(),
                TextInput::make('current_bed_label')->label('Cama/cubículo asignado actualmente')
                    ->helperText('Debe coincidir exactamente con el campo "Cama" de la estancia del paciente para que los eventos se asocien automáticamente.'),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('hospital_device_id')->label('ID dispositivo')->searchable()->sortable(),
            TextColumn::make('model')->label('Modelo')->placeholder('—'),
            TextColumn::make('device_group')->label('Grupo')->placeholder('—'),
            TextColumn::make('icuUnit.name')->label('Unidad')->placeholder('—'),
            TextColumn::make('current_bed_label')->label('Cama actual')->placeholder('Sin asignar'),
            IconColumn::make('is_active')->label('Activa')->boolean(),
            TextColumn::make('infusion_events_count')->label('Eventos')->counts('infusionEvents'),
        ])->defaultSort('hospital_device_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPumpDevices::route('/'),
            'create' => CreatePumpDevice::route('/create'),
            'edit' => EditPumpDevice::route('/{record}/edit'),
        ];
    }
}
