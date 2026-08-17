<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fidelización de clientes — reglas de puntos
    |--------------------------------------------------------------------------
    |
    | - bs_por_punto: bolivianos de compra necesarios para ganar 1 punto
    |   (la acumulación se calcula sobre el total efectivamente pagado).
    | - puntos_por_bs_descuento: puntos necesarios para descontar Bs 1
    |   al canjearlos en el punto de venta.
    |
    | Con los valores por defecto (10 / 10) el cliente acumula 1 punto
    | por cada Bs 10 de compra y recupera 1 % del gasto al canjear.
    |
    */

    'bs_por_punto' => max(1, (int) env('PUNTOS_BS_POR_PUNTO', 10)),

    'puntos_por_bs_descuento' => max(1, (int) env('PUNTOS_VALOR_BS', 10)),

];
