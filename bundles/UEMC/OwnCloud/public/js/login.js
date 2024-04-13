$(document).ready(function() {
    $('#loginButton').click(function(e) {
        e.preventDefault();

        var userName = $('#floatingInput').val();
        var password = $('#floatingPassword').val();
        var url = $('#floatingURL').val();
        var rememberMe = $('#flexCheckDefault').prop('checked');

        var formData = {
            userName: userName,
            password: password,
            url: url,
        };

        $.ajax({
            type: 'POST',
            url: '../login',
            data: formData,
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