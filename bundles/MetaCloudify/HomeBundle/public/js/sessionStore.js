function storeNewAccount(accountId,accountUser,controller,root)
{
    let storedAccounts = JSON.parse(sessionStorage.getItem('storedAccounts')) || {};
    if (!storedAccounts[accountId]) {
        storedAccounts[accountId] = new Account(
            accountId, //accountId
            controller, // controller
            accountUser, // user
            root,      // root
            root,      // pathActual
            root       // parent
        );
        sessionStorage.setItem('storedAccounts', JSON.stringify(storedAccounts));
    }
}

function getAccount(accountId)
{
    return JSON.parse(sessionStorage.getItem('storedAccounts'))[accountId];
}

function getAccounts()
{
    return JSON.parse(sessionStorage.getItem('storedAccounts'));
}

function setAccount(account)
{
    try {
        let storedAccounts = JSON.parse(sessionStorage.getItem('storedAccounts')) ;
        storedAccounts[account.accountId]=account;
        sessionStorage.setItem('storedAccounts', JSON.stringify(storedAccounts));
    }catch (e)
    {
        mostrarModalErrores(e);
    }
}

function deleteSessionAccount(accountId)
{
    let storedAccounts = getAccounts();

    if (storedAccounts) {
        delete storedAccounts[accountId];
        if(Object.keys(storedAccounts).length === 0)
        {
            sessionStorage.removeItem("storedAccounts");
        }else
        {
            sessionStorage.setItem("storedAccounts", JSON.stringify(storedAccounts));
        }
    } else {
        mostrarModalErrores("No hay cuentas almacenadas o el valor no es un array.");
    }
}
function cleanSessionAccounts()
{
    sessionStorage.removeItem("storedAccounts");
}

