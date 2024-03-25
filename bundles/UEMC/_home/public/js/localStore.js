function storeNewAccount(accountId,accountUser,controller,root)
{
    let storedAccounts = JSON.parse(sessionStorage.getItem('storedAccounts')) || {};
    if (!storedAccounts[accountId]) {
        let account = new Account(
            accountId, //accountId
            controller,        // controller
            accountUser, // user
            root,                // root
            root,                // pathActual
            root                 // parent
        );
        storedAccounts[accountId] = account;
        sessionStorage.setItem('storedAccounts', JSON.stringify(storedAccounts));
    }
}

function getAccount(accountId)
{
    return JSON.parse(sessionStorage.getItem('storedAccounts'))[accountId];
}

function setAccount(account)
{
    try {
        let storedAccounts = JSON.parse(sessionStorage.getItem('storedAccounts')) ;
        storedAccounts[account.accountId]=account;
        sessionStorage.setItem('storedAccounts', JSON.stringify(storedAccounts));
    }catch (e)
    {
        console.log(e);
    }

}

function loadSelects() {
    // Obtener cuentas del almacenamiento local
    let storedAccounts = JSON.parse(sessionStorage.getItem('storedAccounts'));

    // Limpiar opciones de los select
    $('#selectTabla1').empty();
    $('#selectTabla2').empty();

    // Agregar opciones al select de cada tabla
    if (storedAccounts && typeof storedAccounts === 'object') {

        $('#selectTabla1').append($('<option>').text('Seleccionar').attr('selected', true).attr('disabled', true));
        $('#selectTabla2').append($('<option>').text('Seleccionar').attr('selected', true).attr('disabled', true));

        let accountsArray = Object.values(storedAccounts);
        accountsArray.forEach(function(account, index) {
            let option = $('<option>').val(account.accountId).text(account.user + ' - ' + account.controller);
            $('#selectTabla1').append(option);
            $('#selectTabla2').append(option.clone()); // Clonar opción para el segundo select
        });
    } else {
        // Manejar caso de no haber cuentas almacenadas
        $('#selectTabla1').append($('<option>').text('No hay cuentas disponibles'));
        $('#selectTabla2').append($('<option>').text('No hay cuentas disponibles'));
    }
}
