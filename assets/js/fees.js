function handle_fees() {
    var index = 0;
    let newFlatFormFees = $('[id^="new_flat_form_fees_"]');
    let count = newFlatFormFees.length;

    if (count > 0) {
        index = count / 3;
    }


    $('input[id^="new_flat_form_fees_"]').each(function(i){
        $(this).addClass('form-control');
        i += 2;
        if (i % 2 === 0) {
            $(this).parent().prepend('<label>Fee <span class="fee-number">' + i / 2 + '</span></label> <span class="remove-fee text-danger"><i class="fas fa-trash-alt"></i></span><br>');
        }
    });

    let takenValues = [];

    newFlatFormFees.each(function(){
        if (this.id.match(/^new_flat_form_fees_\d{1,2}$/)) {
            $(this).addClass('mt-3 col-sm-5 mb-3');
            $(this).find('div').addClass('mb-3');
            $(this).find('input').addClass('mx-2');
        }
        $(this).find( 'input[type=number]').after('<span> zł</span>');
        let lastUnderscoreCharacters = this.id.match(/\d/g);
        lastUnderscoreCharacters = lastUnderscoreCharacters.join("");
        takenValues.push(lastUnderscoreCharacters);
    });

    $('#add-more').click(function() {
        if (index === count / 3 && index > 0) {
            index++;
        }

        let takenValuesToInt = takenValues.map(function (x) {
            return parseInt(x, 10);
        });

        if (takenValuesToInt.includes(index)) {
            let max_of_array = Math.max.apply(Math, takenValues);
            index = max_of_array + 1;

        }

        var prototype = $('#new_flat_form_fees').data('prototype');
        var newForm = prototype.replace(/__name__/g, index);

        $(newForm).appendTo('#fees-container');
        $('#new_flat_form_fees_' + index + '_name').addClass('form-control');
        $('#new_flat_form_fees_' + index + '_value').addClass('form-control');
        $('#new_flat_form_fees_' + index).prepend('<label>Fee <span class="fee-number">' + 1 /* this is just placeholder */ + '</span></label> <span class="remove-fee text-danger"><i class="fas fa-trash-alt"></i></span>').addClass('mt-3 col-sm-5');
        $('#new_flat_form_fees_' + index).find('div').addClass('mb-3');
        $('#new_flat_form_fees_' + index).find('input').addClass('mx-2');
        $('#new_flat_form_fees_' + index).find( 'input[type=number]').after('<span> zł</span>');
        index++;

        $('.fee-number').each(function(i, el) {
            $(el).text((i + 1));
        });
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

export {handle_fees}
