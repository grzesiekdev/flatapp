function handle_furnishings() {
    let index = 0;
    let newFlatFormFurniture = $('[id^="new_flat_form_furnishing_"]');
    let count = newFlatFormFurniture.length;

    if (count > 0) {
        index = count / 3;
    }

    $('input[id^="new_flat_form_furnishing_"]').each(function(i){
        $(this).addClass('form-control');
        i += 2;
        if (i % 2 === 0) {
            $(this).parent().prepend('<label>Fee <span class="fee-number">' + i / 2 + '</span></label> <span class="remove-fee text-danger"><i class="fas fa-trash-alt"></i></span><br>');
        }
    });

    newFlatFormFurniture.each(function(){
        if (this.id.match(/^new_flat_form_fees_\d$/)) {
            $(this).addClass('mt-3 col-sm-5 mb-3');
            $(this).find('div').addClass('mb-3');
            $(this).find('input').addClass('mx-2');
        }
        $(this).find( 'input[type=number]').after('<span> zł</span>');
    });

    $('#add-more').click(function() {
        var prototype = $('#new_flat_form_fees').data('prototype');
        var newForm = prototype.replace(/__name__/g, index);
        let currentNumber = $('#fees-container > div').length + $('#new_flat_form_fees > div').length;
        $(newForm).appendTo('#fees-container');
        $('#new_flat_form_fees_' + index + '_name').addClass('form-control');
        $('#new_flat_form_fees_' + index + '_value').addClass('form-control');
        $('#new_flat_form_fees_' + index).prepend('<label>Fee <span class="fee-number">' + currentNumber + '</span></label> <span class="remove-fee text-danger"><i class="fas fa-trash-alt"></i></span>').addClass('mt-3 col-sm-5');
        $('#new_flat_form_fees_' + index).find('div').addClass('mb-3');
        $('#new_flat_form_fees_' + index).find('input').addClass('mx-2');
        $('#new_flat_form_fees_' + index).find( 'input[type=number]').after('<span> zł</span>');
        index++;
    });

    $(document).on('click', '.remove-fee', function() {
        let parentElement = $(this).parent();
        let grandparentElement = parentElement.parent();
        grandparentElement.remove();
        $('.fee-number').each(function(i, el) {
            $(el).text((i + 1));
        });
    });
}