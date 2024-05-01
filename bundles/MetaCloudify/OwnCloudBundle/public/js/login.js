$(document).ready(function() {

    //Autorellenado de los campos
    if (localStorage.getItem('owncloud')) {

        let storedData = JSON.parse(localStorage.getItem('owncloud'));

        let biblo = atob(storedData.biblo);

        //Es necesaroi hacer un index por si la contraseña lleva ":" podier disitngirla del atob
        let lastColonIndex = biblo.lastIndexOf(':');
        let bibloa = biblo.substring(0, lastColonIndex);
        let biblob = biblo.substring(lastColonIndex + 1);

        $('#floatingBibloa').val(bibloa);
        $('#floatingBiblob').val(biblob);
        $('#floatingURL').val(storedData.url);
    }

    //Boton de login
    $('#loginButton').click(function(e) {
        e.preventDefault();

        let bibloa = $('#floatingBibloa').val();
        let biblob = $('#floatingBiblob').val();
        let url = $('#floatingURL').val();
        let rememberMe = $('#flexCheckDefault').prop('checked');
        let biblo=btoa(bibloa + ':' + biblob);

        let sotreData= {
            'biblo': biblo,
            'url': url
        }

        $.ajax({
            type: 'POST',
            url: '../login',
            data: {
                url: url
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Basic ' + biblo);
            },
            success: function() {
                if(rememberMe)
                {
                    storeBiblo('owncloud',sotreData)
                }
                window.location.href = '../../Home';
            },
            error: function(xhr, status, error) {
                limpiarModalErrores();
                mostrarModalErrores(xhr);
            }
        });
    });
});