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

        /* Datepicker */

        $("#bf-date-start, #bf-date-end").datepicker();

        /* Update Form */

        $("#bf-repeat").on("change", updateForm);

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

        /* Enable form on submit */

        var formSubmit = $("#bf-submit");
        var form = formSubmit.closest("form");

        form.on("submit", function() {
            /* Sync user field: desktop value wins, copy to mobile to prevent empty override */
            var desktopUser = $("#bf-user").val();
            var mobileUser  = $("#bf-user-mobile").val();

            if (desktopUser) {
                $("#bf-user-mobile").val(desktopUser);
            } else if (mobileUser) {
                $("#bf-user").val(mobileUser);
            }

            form.find(":disabled").removeAttr("disabled");

            /* Sync duplicate fields: desktop wins on desktop, mobile wins on mobile.
               PHP takes the last occurrence of each name; mobile fields come last in HTML. */
            if (window.innerWidth >= 601) {
                form.find(".bf-mobile input, .bf-mobile select, .bf-mobile textarea").each(function() {
                    var name = $(this).attr("name");
                    if (!name || name === "bf-user") { return; }
                    var $desktop = form.find(".bf-table [name='" + name + "']").first();
                    if ($desktop.length) {
                        $(this).val($desktop.val());
                    }
                });
            }
        });

    });

    function updateForm()
    {

        /* Datepicker on demand for date end */

        var dateEnd = $("#bf-date-end");
        var repeat = $("#bf-repeat");

        if (repeat.val() === "0") {
            disableFormElement(dateEnd);
        } else {
            enableFormElement(dateEnd);
        }

        var editMode = tagProvider.data("edit-mode-tag");

        if (editMode == "no_subscr") {
            disableFormElement(repeat);
            disableFormElement("#bf-date-end"); 
        }   

        /* Lock specific fields in edit mode */

        var rid = $("#bf-rid");

        if (rid.val()) {
            disableFormElement(repeat);

            if (editMode == "booking") {
                disableFormElement("#bf-time-start");
                disableFormElement("#bf-time-end");
                disableFormElement("#bf-date-start");
                disableFormElement("#bf-date-end");
            } else if (editMode == "reservation") {
                disableFormElement("#bf-user");
                disableFormElement("#bf-sid");
                disableFormElement("#bf-status-billing");
                disableFormElement("#bf-quantity");
                disableFormElement("#bf-notes");
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
