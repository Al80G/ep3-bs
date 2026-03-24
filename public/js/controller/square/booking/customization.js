(function() {

    var playerAutocompleteUrl;
    var validatedFields = {};  /* fieldId -> true/false */

    $(document).ready(function() {

        $("#sb-customization-panel-warning").remove();
        $("#sb-customization-panel").show();

        playerAutocompleteUrl = $("#sb-url-provider").data("player-autocomplete-url");

        $("#sb-quantity").on("change keyup focusout", onQuantityChange);

        onQuantityChange();

        $(".sb-player-names input").on("change keyup focusout", onPlayerNameUpdate);

        $(".sb-product").on("change", onProductChange);

    });

    function onQuantityChange() {
        var quantity = $("#sb-quantity").val();
        var sbButton = $("#sb-button");

        if (sbButton.length) {
            var oldHref = sbButton.attr("href");
            var newHref = oldHref.replace(/q=[0-9]+/, "q=" + quantity);

            sbButton.attr("href", newHref);
        }

        var askNamesPanel = $(".sb-player-names");

        if (askNamesPanel.length) {
            if (quantity > 1) {
                $(".sb-player-name").hide();

                for (var i = 2; i <= quantity; i++) {
                    $(".sb-player-name-" + i).show();
                }

                askNamesPanel.show();

                bindPlayerAutocomplete();

            } else {
                askNamesPanel.hide();
            }

            $(window).trigger("squarebox.update");
        }

        onPlayerNameUpdate();
    }

    function bindPlayerAutocomplete() {
        if (!playerAutocompleteUrl) return;

        $(".sb-player-autocomplete:visible").each(function() {
            if (!$(this).data("ui-autocomplete")) {
                $(this).autocomplete({
                    minLength: 2,
                    source: playerAutocompleteUrl,
                    select: function(event, ui) {
                        $(this).val(ui.item.value);
                        setFieldValid($(this), true);
                        onPlayerNameUpdate();
                        return false;
                    }
                });
            }
            /* Reset validation when user types manually */
            $(this).off("input.playerval").on("input.playerval", function() {
                setFieldValid($(this), false);
                onPlayerNameUpdate();
            });
            $(this).off("focusout.playerval").on("focusout.playerval", function() {
                validateFieldByName($(this));
            });
        });
    }

    function setFieldValid($input, valid) {
        var id = $input.attr("id");
        validatedFields[id] = valid;
        $input.css("border-color", valid ? "" : (($input.val() === "") ? "" : "#c00"));
    }

    function validateFieldByName($input) {
        var name = $.trim($input.val());
        if (!name || !playerAutocompleteUrl) {
            setFieldValid($input, false);
            onPlayerNameUpdate();
            return;
        }
        $.getJSON(playerAutocompleteUrl, { term: name }, function(results) {
            var exact = false;
            $.each(results, function(i, alias) {
                if (alias === name) { exact = true; return false; }
            });
            setFieldValid($input, exact);
            onPlayerNameUpdate();
        });
    }

    function allVisiblePlayerFieldsValid() {
        var allValid = true;
        $(".sb-player-autocomplete:visible").each(function() {
            var id = $(this).attr("id");
            var val = $.trim($(this).val());
            if (val === "") {
                return;
            }
            if (!validatedFields[id]) {
                allValid = false;
                return false;
            }
        });
        return allValid;
    }

    function onPlayerNameUpdate() {
        var sbButton = $("#sb-button");

        if (sbButton.length) {
            var quantity = $("#sb-quantity").val();

            var playerNameMode = $(".sb-player-names-mode").data("mode");
            var playerNameInputs = $(".sb-player-names input:visible");

            if (quantity > 1) {
                var playerNameData = playerNameInputs.serializeArray();
                var playerNameJson = JSON.stringify(playerNameData);
                var playerNameQuery = "pn=" + encodeURIComponent(playerNameJson);
            } else {
                var playerNameQuery = "pn=0";
            }

            sbButton.css({ opacity: 1, "pointer-events": "" });

            /* Hide button if required fields are empty */
            if (playerNameMode == "required") {
                playerNameInputs.each(function() {
                    if (! $(this).val()) {
                        sbButton.css({ opacity: 0, "pointer-events": "none" });
                    }
                });
            }

            /* Hide button if any filled field contains an unregistered name */
            if (!allVisiblePlayerFieldsValid()) {
                sbButton.css({ opacity: 0, "pointer-events": "none" });
            }

            var oldHref = sbButton.attr("href");
            var newHref = oldHref.replace(/pn=[^&]+/, playerNameQuery);

            sbButton.attr("href", newHref);
        }
    }

    function onProductChange() {
        var sbButton = $("#sb-button");

        if (sbButton.length) {
            var products = "";

            $(".sb-product").each(function(index, element) {
                var spid = $(element).data("spid");
                var value = $(element).val();

                if (value > 0) {
                    products += spid + ":" + value + ",";
                }
            });

            if (products) {
                products = products.substr(0, products.length - 1);
            } else {
                products = "0";
            }

            var oldHref = sbButton.attr("href");
            var newHref = oldHref.replace(/p=[0-9\:\,]+/, "p=" + products);

            sbButton.attr("href", newHref);
        }
    }

})();
