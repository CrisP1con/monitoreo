<?php

namespace App\Services;

class DiagnosticReportAnalyzer
{
    /** @param array<int, array<string, mixed>> $events */
    public function analyze(array $events): array
    {
        $text = strtolower(json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $gpu = $this->containsAny($text, ['nvidia', 'nvoglv64.dll', 'dwm.exe', 'display', 'dxgkrnl']);
        $storage = $this->containsAny($text, ['whea', 'disk', 'storport', 'storahci', 'ntfs', 'volmgr']);
        $unexpected = collect($events)->contains(fn (array $event): bool => in_array((int) ($event['id'] ?? 0), [41, 6008], true));

        return [
            'title' => $gpu ? 'Probable falla gráfica NVIDIA/OpenGL/DWM' : ($storage ? 'Probable problema de disco o hardware' : ($unexpected ? 'Apagado o reinicio inesperado' : 'Eventos importantes para revisar')),
            'chain' => $gpu ? 'NVIDIA/OpenGL → aplicación gráfica → DWM → pantalla congelada o reinicio' : ($storage ? 'WHEA/almacenamiento → controlador → posible bloqueo del sistema' : 'Revisar los eventos inmediatamente anteriores al incidente.'),
            'has_unexpected_shutdown' => $unexpected,
            'counts' => collect($events)->countBy('category')->all(),
            'recommendations' => array_values(array_filter([
                $gpu ? 'Revisar o reinstalar limpiamente el controlador NVIDIA.' : null,
                $storage ? 'Revisar SMART, cables, controladores y espacio libre.' : null,
                $unexpected ? 'Verificar Kernel-Power 41, EventLog 6008 y MEMORY.DMP.' : null,
            ])),
        ];
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
