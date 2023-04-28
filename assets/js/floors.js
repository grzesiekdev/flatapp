function handle_floor_select() {
    // Get references to the floor and max floor select elements
    var $floorSelect = $('#new_flat_form_floor');
    var $maxFloorSelect = $('#new_flat_form_maxFloor');

    // When the user selects a floor, update the max floor options to exclude invalid values
    $floorSelect.on('change', function() {
        var selectedFloor = parseInt($floorSelect.val(), 10);
        $maxFloorSelect.find('option').prop('disabled', false); // enable all options

        // Disable max floor options that are less than the selected floor
        for (var i = 1; i <= selectedFloor; i++) {
            $maxFloorSelect.find('option[value="' + i + '"]').prop('disabled', true);
        }
    });

    // When the user selects a max floor, update the floor options to exclude invalid values
    $maxFloorSelect.on('change', function() {
        var selectedMaxFloor = parseInt($maxFloorSelect.val(), 10);
        $floorSelect.find('option').prop('disabled', false); // enable all options

        // Disable floor options that are greater than the selected max floor
        for (var i = selectedMaxFloor + 1; i <= 16; i++) {
            $floorSelect.find('option[value="' + i + '"]').prop('disabled', true);
        }
    });
}

export {handle_floor_select}