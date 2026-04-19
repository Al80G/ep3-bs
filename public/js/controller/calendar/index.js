(function() {

    var calendar;

    var squarebox;
    var squareboxShutdown = false;

    var squareboxOverlay;

    var loadingDelay = 300;
    var animationDuration = 350;

    $(document).ready(function() {

        calendar = $(".calendar-table");

        /* Squarebox */

        calendar.on("click", "a.calendar-cell", function(event) {
            var that = $(this);

            if (! that.hasClass("squarebox-external-link")) {
                event.preventDefault();

                if (!squarebox) {
                    event.stopPropagation();

                    loadSquarebox(that.attr("href"));
                }
            }
        });

        $(window).resize(updateSquarebox);

        $(window).on("squarebox.update", updateSquarebox);

        $("body").on("click", "#squarebox-overlay", function() {
            removeSquarebox();
        });

        /* Group highlighting */

        $("a.calendar-cell").hover(function() {
            var that = $(this);
            var classes = that.attr("class");
            var group = classes.match(/cc-group-\d+/);

            if (group) {
                var groupMembers = $("a." + group);

                groupMembers.each(function() {
                    $(this).data("original-style", $(this).attr("style"));
                });

                groupMembers.css({"opacity": 0.9, "background-color": that.css("background-color")});

                that.css("opacity", 1.0);
            }
        }, function() {
            var that = $(this);
            var classes = that.attr("class");
            var group = classes.match(/cc-group-\d+/);

            if (group) {
                var groupMembers = $("a." + group);

                groupMembers.each(function() {
                    $(this).attr("style", $(this).data("original-style"));
                });
            }
        });

        /* Update calendar */

        updateCalendarCols();
        $(window).resize(updateCalendarCols);
        $(document).on("updateLayout", updateCalendarCols);

        /* Update calendar groups */
        var groups = [".cc-event", ".cc-single", ".cc-own", ".cc-multiple", ".cc-multiple-14", ".cc-conflict"];
        groupCalendarCols(groups);
        $(window).resize(function(evt) { groupCalendarCols(groups); });
        $(document).on("updateLayout", function(evt) { groupCalendarCols(groups); });

        /* Re-calculate layout after orientation change. */
        function doLayoutUpdate() {
            $(".calendar-date-col").css("width", "");
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    updateCalendarCols();
                    groupCalendarCols(groups);
                });
            });
        }

        /* iOS Webclip (standalone + user-scalable=0): the viewport does not resize on rotation.
           Fix: temporarily remove the scale constraints so iOS reflows, poll until window.innerWidth
           actually changes, then restore the meta and recalculate layout. */
        if (window.navigator.standalone) {
            var viewportMeta = document.querySelector("meta[name='viewport']");
            var viewportMetaOriginal = viewportMeta ? viewportMeta.getAttribute("content") : null;

            window.addEventListener("orientationchange", function() {
                var prevWidth = window.innerWidth;

                if (viewportMeta) {
                    viewportMeta.setAttribute("content", "width=device-width, initial-scale=1.0");
                }

                var fallbackTimer;
                var pollTimer = setInterval(function() {
                    if (window.innerWidth !== prevWidth) {
                        clearInterval(pollTimer);
                        clearTimeout(fallbackTimer);
                        if (viewportMeta && viewportMetaOriginal) {
                            viewportMeta.setAttribute("content", viewportMetaOriginal);
                        }
                        doLayoutUpdate();
                    }
                }, 50);

                fallbackTimer = setTimeout(function() {
                    clearInterval(pollTimer);
                    if (viewportMeta && viewportMetaOriginal) {
                        viewportMeta.setAttribute("content", viewportMetaOriginal);
                    }
                    doLayoutUpdate();
                }, 100);
            });
        }

    });

    function loadSquarebox(href)
    {
        var calendarSquareboxTemplate = $("#calendar-squarebox-template");

        if (calendarSquareboxTemplate.length) {
            populateSquarebox( calendarSquareboxTemplate.html() );
        } else {
            populateSquarebox('<div class="padded">...</p>');
        }

        squarebox.clearQueue().delay(loadingDelay).queue(function() {
            $.ajax({
                "cache": false,
                "data": { "ajax": true },
                "dataType": "html",
                "error": function() {
                    if (squarebox && ! squareboxShutdown) {
                        window.location.href = href;
                    }
                },
                "success": function (data) {
                    if (squarebox && ! squareboxShutdown) {
                        populateSquarebox(data);

                        squarebox.find(".no-ajax").remove();
                        squarebox.find(".datepicker").datepicker();

                        squarebox.find(".inline-label-container").each(function() {
                            updateInlineLabel( $(this) );
                        });

                        squarebox.append('<a href="#" class="squarebox-primary-close-link squarebox-close-link">&times;</a>');

                        updateSquarebox();

                        /* Recognize squarebox internal links */

                        squarebox.on("click", "a.squarebox-internal-link", function(event) {
                            event.preventDefault();

                            loadSquarebox( $(this).attr("href") );
                        });

                        /* Recognize squarebox close links */

                        squarebox.on("click", "a.squarebox-close-link", function(event) {
                            event.preventDefault();

                            removeSquarebox();
                        });
                    }
                },
                "url": href
            });

            $(this).dequeue();
        });
    }

    function prepareSquarebox()
    {
        if (! squareboxOverlay) {
            squareboxOverlay = $('<div id="squarebox-overlay"></div>').css({
                "position": "fixed",
                "z-index": 1532,
                "opacity": 0.00,
                "width": "100%", "height": "100%",
                "left": 0, "top": 0,
                "background": "#333"
            });

            $("body").prepend(squareboxOverlay);
        }

        if (! squarebox) {
            squarebox = $('<div class="panel"></div>').css({
                "position": "fixed",
                "z-index": 1536,
                "box-sizing": "border-box",
                "overflow-x": "hidden",
                "top": "50%",
                "left": "50%",
                "transform": "translate(-50%, -50%)"
            });

            $("body").prepend(squarebox);
        }
    }

    function populateSquarebox(content)
    {
        prepareSquarebox();

        squarebox.clearQueue();
        squarebox.css("opacity", 0.01);
        squarebox.html(content);

        updateSquarebox();

        squarebox.fadeTo(animationDuration, 1.00);

        fadeOutContent();
    }

    function updateSquarebox()
    {
        if (squarebox) {
            if (squarebox.find(".bf-table").length && $(window).width() > 600) {
                squarebox.css({ "width": "auto", "max-width": "none" });
                var naturalWidth = squarebox.outerWidth();
                var maxW = Math.min(naturalWidth, $(window).width() * 0.96);
                squarebox.css({ "width": maxW, "max-width": maxW });
            } else {
                squarebox.css({ "width": "80vw", "max-width": "480px" });
            }
            squarebox.css({
                "top": "50%",
                "left": "50%",
                "transform": "translate(-50%, -50%)"
            });
        }
    }

    function removeSquarebox()
    {
        if (squarebox) {
            squareboxShutdown = true;

            squarebox.clearQueue().fadeOut(animationDuration, function() {
                if (squarebox) {
                    squarebox.remove();
                    squarebox = undefined;
                }

                squareboxShutdown = false;
            });

            fadeInContent();
        }
    }

    function fadeOutContent()
    {
        if (squareboxOverlay) {
            squareboxOverlay.clearQueue().fadeTo(animationDuration, 0.75);
        }
    }

    function fadeInContent()
    {
        if (squareboxOverlay) {
            squareboxOverlay.clearQueue().fadeTo(animationDuration, 0.00, function() {
                if (squareboxOverlay) {
                    squareboxOverlay.remove();
                    squareboxOverlay = undefined;
                }
            });
        }
    }

    function updateCalendarCols()
    {
        var calendarWidth = $("#calendar").width();
        var calendarLegendColWidth = $(".calendar-time-col, .calendar-square-col").width();

        var calendarDateCols = $(".calendar-date-col:visible");

        if (calendarWidth && calendarLegendColWidth && calendarDateCols.length) {
            calendarDateCols.width( Math.floor((calendarWidth - calendarLegendColWidth) / calendarDateCols.length) );
        }
    }

    function groupCalendarCols(groups)
    {
        /* Remove stale overlays so they are rebuilt with correct dimensions after resize/orientation change */
        $("[id^='cc-group-'][id*='-overlay-']").remove();

        setTimeout(function(){
        groups.forEach(function(group, index) {
           $(".calendar-date-col").each(function(dateIndex) {
               var calendarDateCol = $(this);
   
               var groupCols = [];
   
               calendarDateCol.find(group).each(function() {
                   var classes = $(this).attr("class");
                   var groupCol = classes.match(/cc-group-\d+/);
   
                   if (groupCol) {
                       if ($.inArray(groupCol, groupCols) === -1) {
                           groupCols.push(groupCol);
                       }
                   }
               });
   
               var groupColsLength = groupCols.length;
   
               var diffy = 0;
   
               for (var i = 0; i <= groupColsLength; i++) {
                   var groupCol = groupCols[i] + "";
   
                   var groupColCellFirst = calendarDateCol.find("." + groupCol + ":first");
                   var groupColCellLast = calendarDateCol.find("." + groupCol + ":last");
   
                   var posFirst = groupColCellFirst.position();
                   var posLast = groupColCellLast.position();
   
                   if (posFirst && posLast) {
                       var startX = Math.floor(posFirst.left) - 1;
                       var startY = Math.floor(posFirst.top) - 1;
   
                       var endX = Math.ceil(posLast.left) + 1;
                       var endY = Math.ceil(posLast.top) + 1;
   
                       var groupWidth = Math.round((endX + groupColCellLast.outerWidth()) - startX);
                       var groupHeight = Math.round((endY + groupColCellLast.outerHeight()) - startY);
   
                       /* Create group overlay */
   
                       var groupColOverlay = $("#" + groupCol + "-overlay-" + dateIndex);
   
                       if (! groupColOverlay.length) {
                           // var diffy = 29;
                           groupColOverlay = groupColCellFirst.clone();
                           groupColOverlay.appendTo( groupColCellFirst.closest("td") );
                           groupColOverlay.attr("id", groupCol + "-overlay-" + dateIndex);
                           groupColOverlay.removeClass(groupCol);
                           // get te from  groupColCellLast
                           var te = groupColCellLast.attr("href").match(/te=\d+:\d+/);
                           groupColOverlay.attr("href", groupColOverlay.attr("href").replace(/te=\d+:\d+/,te));                          
                       }
   
                       var groupColOverlayLabel = groupColOverlay.find(".cc-label");
   
                       groupColOverlay.css({
                           "position": "absolute",
                           "z-index": 128,
                           "left": startX+1, 
                           "top": startY+1,
                           "width": groupWidth-2,
                           "height": groupHeight-2,
                           "padding": 0
                       });
   
                       groupColOverlayLabel.css({
                           "height": "auto",
                           "font-size": "12px",
                           "line-height": 1.2
                       });
   
                       groupColOverlayLabel.css({
                           "position": "relative",
                           "top": Math.round((groupHeight / 2) - (groupColOverlayLabel.height() / 2)-2)
                       });
                   }
               }
           });
        });
        }, 1); 
    }

})();
