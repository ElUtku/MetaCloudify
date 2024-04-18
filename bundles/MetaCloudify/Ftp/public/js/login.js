$(document).ready(function() {
    $('#loginButton').click(function(e) {
        e.preventDefault();

        var userName = $('#floatingInput').val();
        var password = $('#floatingPassword').val();
        var url = $('#floatingURL').val();
        var rememberMe = $('#flexCheckDefault').prop('checked');

        $.ajax({
            type: 'POST',
            url: '../login',
            data: {
                url: url,
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Basic ' + btoa(userName + ':' + password));
            },
            success: function(response) {
                window.location.href = '../../_home';
            },
            error: function(xhr, status, error) {
                limpiarModalErrores();
                mostrarModalErrores(xhr);
            }
        });
    });
});