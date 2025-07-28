import { registerPaymentMethod } from '@woocommerce/blocks-registry';

const BatPayPaymentMethod = {
    name: 'batpay',
    label: 'BatPay - Pago Seguro',
    canMakePayment: () => true, // Aquí podrías agregar condiciones de pago.
    content: () => (
        <div>
            <p>Paga de forma segura con BatPay.</p>
        </div>
    ),
    edit: () => <div>Configurando BatPay...</div>,
};

registerPaymentMethod(BatPayPaymentMethod);
