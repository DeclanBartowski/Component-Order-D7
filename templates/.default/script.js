$(document).on('submit', '#tq_payment', function () {
    let $this = $(this);
    let error = $('.error');
    error.html('');
    error.hide();
    BX.ajax.runComponentAction('2quick:tq_payment',
        'payment', { // Вызывается без постфикса Action
            mode: 'class',
            data: {
                request: $this.serializeArray()
            }, // ключи объекта data соответствуют параметрам метода
        })
        .then(
            function (response) {
                console.log('success');
                console.log(response);
                $('#tq_payment').html('');
                $('#redirectLink').html(response.data);
            },
            function (response) {
                for (var key in response.errors) {
                    $('.error').append(response.errors[key].message + '<br>');
                }
                error.show();

            }
        );
    return false;
});
