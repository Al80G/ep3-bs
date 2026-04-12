(function() {

    var urlProvider;
    var tagProvider;

    $(document).ready(function() {

        urlProvider = $("#bf-url-provider");
        tagProvider = $("#bf-tag-provider");

        /* Autocomplete for user */

        var userInput = $("#bf-user");

        userInput.autocomplete({
            "minLength": 1,
            "source": urlProvider.data("user-autocomplete-url")
        });

        $("#bf-user-mobile").autocomplete({
            "minLength": 1,
            "source": urlProvider.data("user-autocomplete-url"),
            "select": function(event, ui) {
                $(this).val(ui.item.value);
                $("#bf-user").val(ui.item.value);
                return false;
            }
        });

        /* Autocomplete for additional players */

        $(".player-autocomplete").autocomplete({
            "minLength": 1,
            "source": urlProvider.data("user-autocomplete-url")
        });

        /* Timepicker: replace time text inputs with 30-minute interval selects */

        function makeTimeSelect(currentVal, name, id, style) {
            var $select = $('<select>').attr('name', name).attr('id', id).attr('style', style || 'width: 80px;');
            for (var h = 0; h <= 24; h++) {
                var steps = (h === 24) ? [0] : [0, 30];
                for (var s = 0; s < steps.length; s++) {
                    var time = ('0' + h).slice(-2) + ':' + ('0' + steps[s]).slice(-2);
                    $('<option>').val(time).text(time).prop('selected', time === currentVal).appendTo($select);
                }
            }
            if ($select.val() !== currentVal) {
                $('<option>').val(currentVal).text(currentVal).prop('selected', true).prependTo($select);
            }
            return $select;
        }

        $("[name='bf-time-start'], [name='bf-time-end']").each(function() {
            var $input = $(this);
            var $select = makeTimeSelect($input.val(), $input.attr('name'), $input.attr('id'), $input.attr('style'));
            $input.replaceWith($select);
        });

        /* Datepicker: jQuery UI on desktop, native type="date" on mobile */

        $(".bf-table [name='bf-date-start'], .bf-table [name='bf-date-end']").datepicker();

        $(".bf-mobile [name='bf-date-start'], .bf-mobile [name='bf-date-end']").each(function() {
            var $input = $(this);
            var parts = $input.val().match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
            if (parts) {
                $input.val(parts[3] + '-' + ('0' + parts[2]).slice(-2) + '-' + ('0' + parts[1]).slice(-2));
            }
            $input.attr('type', 'date');
        });

        /* Update Form */

        $("[name='bf-repeat']").on("change", function() {
            $("[name='bf-repeat']").val($(this).val());
            updateForm();
        });

        updateForm();

        /* Sync Ballmaschine checkboxes between desktop and mobile */

        $("#bf-ballmaschine").on("change", function() {
            $("#bf-ballmaschine-mobile").prop("checked", $(this).is(":checked"));
        });

        $("#bf-ballmaschine-mobile").on("change", function() {
            $("#bf-ballmaschine").prop("checked", $(this).is(":checked"));
        });

        /* Exclusive edit fields */

        var $editUser = $('#bf input[name="bf-edit-user"]');
        var $editBills = $('#bf input[name="bf-edit-bills"]');

        if ($editUser.length && $editBills.length) {
            $editUser.on('change', function() {
                $editBills.prop('checked', false);
            });

            $editBills.on('change', function() {
                $editUser.prop('checked', false);
            });
        }

        /* Submit handler */

        var formSubmit = $("#bf-submit");
        var form = formSubmit.closest("form");

        form.on("submit", function() {
            if (window.innerWidth >= 601) {
                /* Desktop: re-enable disabled desktop fields, disable mobile section */
                form.find(".bf-table :disabled").removeAttr("disabled");
                form.find(".bf-mobile input, .bf-mobile select, .bf-mobile textarea").prop("disabled", true);
            } else {
                /* Mobile: re-enable disabled mobile fields, disable desktop section */
                form.find(".bf-mobile :disabled").removeAttr("disabled");
                form.find(".bf-table input, .bf-table select, .bf-table textarea").prop("disabled", true);
            }
        });

    });

    function updateForm()
    {
        var repeat = $("[name='bf-repeat']");

        if (repeat.first().val() === "0") {
            disableFormElement($("[name='bf-date-end']"));
        } else {
            enableFormElement($("[name='bf-date-end']"));
        }

        var editMode = tagProvider.data("edit-mode-tag");

        if (editMode == "no_subscr") {
            disableFormElement(repeat);
            disableFormElement($("[name='bf-date-end']"));
        }

        /* Lock specific fields in edit mode */

        var rid = $("#bf-rid");

        if (rid.val()) {
            disableFormElement(repeat);

            if (editMode == "booking") {
                disableFormElement($("[name='bf-time-start']"));
                disableFormElement($("[name='bf-time-end']"));
                disableFormElement($("[name='bf-date-start']"));
                disableFormElement($("[name='bf-date-end']"));
            } else if (editMode == "reservation") {
                disableFormElement($("[name='bf-user']"));
                disableFormElement($("[name='bf-sid']"));
                disableFormElement($("#bf-status-billing"));
                disableFormElement($("[name='bf-quantity']"));
                disableFormElement($("[name='bf-notes']"));
            }
        }
    }

    function disableFormElement(element)
    {
        if (typeof element == "string") {
            element = $(element);
        }

        element.attr("disabled", "disabled");
        element.css("opacity", 0.5);
    }

    function enableFormElement(element)
    {
        if (typeof element == "string") {
            element = $(element);
        }

        element.removeAttr("disabled");
        element.css("opacity", 1.0);
    }

})();
