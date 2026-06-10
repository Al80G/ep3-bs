(function() {

    var playerAutocompleteUrl;
    var validatedFields = {};  /* fieldId -> true/false */
    var currentUser;

    $(document).ready(function() {

        $("#sb-customization-panel-warning").remove();
        $("#sb-customization-panel").show();

        playerAutocompleteUrl = $("#sb-url-provider").data("player-autocomplete-url");
        currentUser = $("#sb-url-provider").data("current-user");

        $("#sb-quantity").on("change keyup focusout", onQuantityChange);

        onQuantityChange();

        $(".sb-player-names input").on("change keyup focusout", onPlayerNameUpdate);

        $(".sb-product").on("change", onProductChange);

        $("#sb-ballmaschine").on("change", onBallmaschineChange);

    });

    function applyPeakEinzelRestriction(href) {
        var urlProvider = $("#sb-url-provider");
        var peakDays  = String(urlProvider.data("peak-einzel-days")  || "");
        var peakStart = String(urlProvider.data("peak-einzel-start") || "");
        var peakMax   = parseInt(urlProvider.data("peak-einzel-max") || "0");

        if (!peakDays || !peakStart || !peakMax) return href;

        var dsMatch = href.match(/ds=([^&]+)/);
        var tsMatch = href.match(/ts=([^&]+)/);
        var teMatch = href.match(/te=([^&]+)/);
        if (!dsMatch || !tsMatch || !teMatch) return href;

        var ds = dsMatch[1];
        var ts = decodeURIComponent(tsMatch[1]);
        var te = decodeURIComponent(teMatch[1]);

        // ISO day of week: Mon=1 … Sun=7
        var date = new Date(ds);
        var isoDay = date.getDay() === 0 ? 7 : date.getDay();
        var allowedDays = peakDays.split(",").map(function(d) { return parseInt(d.trim()); });
        if (allowedDays.indexOf(isoDay) === -1) return href;

        var tsParts = ts.split(":");
        var tsMinutes = parseInt(tsParts[0]) * 60 + parseInt(tsParts[1]);
        var peakParts = peakStart.split(":");
        var peakMinutes = parseInt(peakParts[0]) * 60 + parseInt(peakParts[1]);
        if (tsMinutes < peakMinutes) return href;

        var teParts = te.split(":");
        var teMinutes = parseInt(teParts[0]) * 60 + parseInt(teParts[1]);
        var maxTeMinutes = tsMinutes + peakMax;
        if (teMinutes <= maxTeMinutes) return href;

        var newTeH = Math.floor(maxTeMinutes / 60);
        var newTeM = maxTeMinutes % 60;
        var newTe = (newTeH < 10 ? "0" : "") + newTeH + ":" + (newTeM < 10 ? "0" : "") + newTeM;
        return href.replace(/te=[^&]+/, "te=" + newTe);
    }

    function onQuantityChange() {
        var quantity = $("#sb-quantity").val();
        var sbButton = $("#sb-button");

        if (sbButton.length) {
            var oldHref = sbButton.attr("href");
            var newHref = oldHref.replace(/q=[0-9]+/, "q=" + quantity);

            if (String(quantity) === "2") {
                newHref = applyPeakEinzelRestriction(newHref);
            }

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
        /* Prevent adding yourself as a player */
        if (currentUser && name === currentUser) {
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

            var ballmaschineChecked = $("#sb-ballmaschine").is(":checked");

            /* Hide button if required fields are empty (unless Ballmaschine is selected) */
            if (playerNameMode == "required" && !ballmaschineChecked) {
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

    function onBallmaschineChange() {
        var sbButton = $("#sb-button");

        if (sbButton.length) {
            var bm = $("#sb-ballmaschine").is(":checked") ? "1" : "0";
            var oldHref = sbButton.attr("href");
            var newHref = oldHref.replace(/bm=[01]/, "bm=" + bm);

            sbButton.attr("href", newHref);
        }

        onPlayerNameUpdate();
    }

})();
