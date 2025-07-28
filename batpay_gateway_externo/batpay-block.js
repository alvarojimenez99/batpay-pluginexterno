const { registerBlockType } = wp.blocks;

registerBlockType('batpay/block', {
    title: 'Botón de Pago BatPay',
    icon: 'cart',
    category: 'common',
    edit: () => 'Bloque para procesar pagos con BatPay.',
    save: () => null, // Renderizado dinámicamente por PHP.
});
