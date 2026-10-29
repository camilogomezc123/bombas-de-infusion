<?php

namespace App\Support\Posuci;

use Illuminate\Support\Carbon;

/**
 * Frase o consejo breve que cambia todos los días en el Inicio del portal — le da al
 * paciente/familia una razón para entrar aunque ya hayan hecho todo lo de hoy. Son
 * mensajes genéricos de ánimo/autocuidado, nunca instrucciones clínicas (dosis,
 * diagnósticos, etc.) — para eso está el equipo tratante, no un mensaje rotativo.
 */
class DailyTip
{
    private const TIPS = [
        '💧 Tomar agua seguido ayuda a tu cuerpo a recuperarse — ten una botella cerca hoy.',
        '🚶 Un paseo corto, aunque sea unos pasos, cuenta como avance. No hace falta que sea largo.',
        '😴 Dormir bien es parte del tratamiento, no un lujo — trata de mantener un horario.',
        '📝 Escribir cómo te sientes, aunque sea una frase, ayuda a tu equipo a entenderte mejor.',
        '🤝 Pedir ayuda no es rendirse — es parte de recuperarse bien.',
        '🍽️ Comer despacio y en varias veces al día puede ser más fácil que tres comidas grandes.',
        '🧘 Unos minutos de respiración lenta pueden bajar la ansiedad — inhala 4 segundos, exhala 6.',
        '☀️ Un poco de luz natural en el día ayuda a dormir mejor en la noche.',
        '📞 Si algo te preocupa, repórtalo — tu equipo prefiere saberlo temprano.',
        '🎯 Las metas pequeñas también cuentan. Hoy no tiene que ser un gran día para ser un buen día.',
        '👨‍👩‍👧 Si eres cuidador: cuidarte a ti también es parte de cuidar bien a tu familiar.',
        '🩺 Anotar tus síntomas apenas los notas es más preciso que tratar de recordarlos después.',
        '💊 Si tienes dudas sobre un medicamento, es mejor preguntar que adivinar.',
        '🧠 Es normal tener días difíciles emocionalmente después de una hospitalización así. No estás solo/a.',
        '🛌 El cansancio después de una UCI puede durar semanas o meses — sé paciente contigo mismo/a.',
        '📅 Anotar tus citas apenas te las dan evita el estrés de último momento.',
        '🗣️ Hablar de lo que viviste, cuando te sientas listo/a, puede ayudar a procesarlo.',
        '🏡 Pequeños cambios en casa (una silla cerca, buena luz) pueden hacer las tareas diarias más fáciles.',
        '💪 Cada semana que pasa, tu cuerpo y tu mente siguen sanando, aunque no siempre se sienta así.',
        '👂 Si tu familia te dice que te ve distinto/a, vale la pena hablarlo con tu equipo.',
        '🌱 La recuperación no es una línea recta — los días difíciles no borran el avance ya hecho.',
        '📖 Revisar tu "Antes y ahora" de vez en cuando ayuda a ver qué tanto has avanzado.',
        '🧩 Si algo del tratamiento no tiene sentido para ti, pregunta — entender ayuda a seguirlo mejor.',
        '❤️ Está bien celebrar lo pequeño: hoy comiste mejor, hoy dormiste más, hoy hablaste con alguien.',
    ];

    public static function forDate(?Carbon $date = null): string
    {
        $date ??= now();

        return self::TIPS[$date->dayOfYear % count(self::TIPS)];
    }
}
