document.addEventListener('DOMContentLoaded', () => {
    const batPayButton = document.getElementById('batpay-button');
    if (batPayButton) {
        batPayButton.addEventListener('click', () => {
            // Aquí puedes manejar redirecciones o más lógica según sea necesario.
            alert('Redirigiendo a BatPay...');
        });
    }
});
