<?php

namespace App\Services;

class PaymentMethodsService
{
    /**
     * Datos completos de los métodos de pago disponibles, incluyendo
     * icono SVG, moneda, descripción y si está en modo simulación.
     *
     * @return array<string, array{id: string, title: string, description: string, currency: string, simulacion: bool, svg: string, colorBg: string}>
     */
    public function all(): array
    {
        $simulacionStripe = app(PaymentService::class)->usaSimulacion('Stripe');
        $simulacionPaypal = app(PaymentService::class)->usaSimulacion('PayPal');
        $simulacionLocal = true; // métodos locales (Tarjeta, Yape) no tienen API pública

        return [
            'Stripe' => [
                'id' => 'Stripe',
                'title' => 'Stripe',
                'description' => 'Paga con tarjeta de crédito/debito internacional.',
                'currency' => 'USD',
                'simulacion' => $simulacionStripe,
                'svg' => $this->stripeSvg(),
                'colorBg' => 'bg-white',
                'colorText' => 'text-gray-700',
            ],
            'PayPal' => [
                'id' => 'PayPal',
                'title' => 'PayPal',
                'description' => 'Paga con tu cuenta PayPal.',
                'currency' => 'USD',
                'simulacion' => $simulacionPaypal,
                'svg' => $this->paypalSvg(),
                'colorBg' => 'bg-white',
                'colorText' => 'text-blue-800',
            ],
            'Tarjeta' => [
                'id' => 'Tarjeta',
                'title' => 'Tarjeta (Débito/Crédito)',
                'description' => 'Paga con tarjeta en bolivianos (BOB).',
                'currency' => 'BOB',
                'simulacion' => $simulacionLocal,
                'svg' => $this->cardSvg(),
                'colorBg' => 'bg-emerald-50',
                'colorText' => 'text-emerald-800',
            ],

            'Yape' => [
                'id' => 'Yape',
                'title' => 'Yape',
                'description' => 'Pago electrónico BVL en bolivianos.',
                'currency' => 'BOB',
                'simulacion' => $simulacionLocal,
                'svg' => $this->yapeSvg(),
                'colorBg' => 'bg-yellow-50',
                'colorText' => 'text-yellow-800',
            ],
        ];
    }

    /**
     * Lista de métodos de pago en línea (no Efectivo).
     */
    public function onlineMethods(): array
    {
        return [
            'Stripe' => $this->all()['Stripe'],
            'PayPal' => $this->all()['PayPal'],
            'Tarjeta' => $this->all()['Tarjeta'],
            'Yape' => $this->all()['Yape'],
        ];
    }

    // ---------- Iconos SVG ----------

    private function stripeSvg(): string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M12.5 2C6.1 2 1.2 6.8 1.2 13c0 5.2 3.8 9.6 9.3 10.7.7-.1 1.3-.4 1.8-.7-.5 1.4-1.7 2.5-3.2 2.8-1.1.2-2.2-.4-2.7-1.2-.5 1-1.5 2-2.7 2-1.5 0-2.6-1.2-2.6-3 0-1.4.8-2.4 2.2-3.2-1.4-.3-2.7-1-2.7-2.5 0-1.8 1.2-3.3 2.9-3.9.8-.3 1.7-.4 2.5-.4h4c.8 0 1.7.1 2.5.4 1.7.6 2.9 2.1 2.9 3.9 0 1.5-1.1 3.2-2.6 3C14.4 21 13.2 20.5 11.1 20 9.7 19.6 8.9 18 8.9 16c0-2.2 1.5-3.8 3.5-3.9.6-.1 1.1-.3 1.6-.4C15.3 9.5 16 7.8 16 6c0-3.2-2.3-6-5.8-6z"/></svg>';
    }

    private function paypalSvg(): string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M7.076 21.337H2.47a.687.687 0 0 1-.684-.684v-2.537c0-.379.305-.685.684-.685h4.602v-2.35a.686.686 0 0 0-.686-.685c-.375 0-.682.306-.682.685v2.35h-4.6c-.683 0-.683.683-.683.683v2.536c0 .378.305.684.683.684h4.61c.264 0 .487.222.487.486v.154c0 .264-.223.487-.487.487h-4.61v2.35c0 .379.307.685.687.685.375 0 .682-.306.682-.685v-.15c0-.265.223-.488.487-.488h4.605c.684 0 .684-.684.684-.684v-.488c0-.684-.684-.684-.684-.684h-4.605V4.61c0-.684-.684-.684-.684-.684s-.684.684-.684.684v.487H6.392c-.684 0-.684.684-.684.684s.684.684.684.684h1.355v4.6c0 .684.684.684.684.684s.684-.684.684-.684v-4.6h3.954v2.35c0 .379.306.685.684.685.375 0 .682-.306.682-.685V4.61h4.6v2.35c0 .379.306.685.684.685.375 0 .682-.306.682-.685v-.15c.002-.68-.683-.684-1.367-.684h-1.355v4.6c0 .684.684.684.684.684s.684-.684.684-.684V4.61H10.86v4.6q0 .054-.003.108c0 .684.684.687 1.367.687.683 0 1.367-.684 1.367-.687v-4.6H17.3v2.537c0 .379.306.684.684.684s.684-.305.684-.684v-2.35H17.3v2.35c0 .379.306.684.684.684s.684-.305.684-.684v-2.35H17.3v2.537c0 .379.306.684.684.684s.684-.305.684-.684v-2.35h4.61c.683 0 .683.683.683.683v2.537c0 .683-.683.683-.683.683h-4.61c-.683 0-.683-.683-.683-.683V2.47c-.264-.264-.685-.264-1.021 0-.336.336-.336.856 0 1.192v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V4.614H16.58c.684 0 .684.685.684.685s-.684.684-.684.684v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V2.47c-.264-.264-.685-.264-1.021 0-.336.336-.336.856 0 1.192v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V2.47c-.264-.264-.685-.264-1.021 0-.336.336-.336.856 0 1.192v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V2.47h.16v4.602c0 .683.684.684.684.684s.684-.684.684-.684v-.15a1.526 1.526 0 0 0 -1.02-1.192 1.524 1.524 0 0 0 -1.02 1.192V.684c-.264-.264-.685-.264-1.021 0-.336.336-.336.856 0 1.192v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V.684H7.076c-.684 0-.684.684-.684.684s.684.684.684.684v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V.684c-.264-.264-.685-.264-1.021 0-.336.336-.336.856 0 1.192v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V.684H7.076c-.684 0-.684.684-.684.684s.684.684.684.684v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V.684c-.264-.264-.685-.264-1.021 0-.336.336-.336.856 0 1.192v.15a1.526 1.526 0 0 0 1.02 1.192 1.524 1.524 0 0 0 1.02-1.192V.684H7.076z"/></svg>';
    }

    private function cardSvg(): string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M3 3h18v14H3zM5 5h8v2H5zM5 7h11v2H5zM5 9h8v2H5zM5 11h11v2H5zM5 13h8v2H5z"/></svg>';
    }

    private function yapeSvg(): string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 8 8 8 8-4.48 8-8-4.48-8-8-8zm0 14a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm-1-5h2v2h-2z"/></svg>';
    }
}
