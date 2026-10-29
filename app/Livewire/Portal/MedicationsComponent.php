<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use Livewire\Component;

/**
 * Solo lectura: la lista de medicamentos conciliados la documenta el staff, el
 * paciente/cuidador la consulta. Si tiene una duda sobre un medicamento, se le dirige
 * a "Necesito ayuda" (SupportRequest, tipo duda_medicamento) en vez de agregar aquí
 * otra capa de "revisado/confirmado".
 */
class MedicationsComponent extends Component
{
    public function render()
    {
        $case = PortalHomeController::currentCase();

        return view('livewire.portal.medications-component', [
            'reconciliation' => $case?->medicationReconciliation()
                ->with(['items' => fn ($query) => $query->orderBy('sort_order')])
                ->first(),
        ]);
    }
}
