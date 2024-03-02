function storeAccount(accountId,accountUser,controller,root)
{
    let storedAccounts = JSON.parse(localStorage.getItem('storedAccounts')) || {};
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
        localStorage.setItem('storedAccounts', JSON.stringify(storedAccounts));
    }
}

function getAccount(accountId)
{
    return JSON.parse(localStorage.getItem('storedAccounts'))[accountId];
}