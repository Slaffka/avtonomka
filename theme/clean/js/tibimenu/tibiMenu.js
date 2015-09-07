(function() {
    $(document).ready(function(){
        $(".bt-menu-trigger").colMenu();
    });

    (function( $ ) {
        var tibiMenu = function (element, options) {
            var self = this;

            this.$trigger     = $(element);
            this.$wrapper     = this.$trigger.parent("div");
            this.$menu        = this.$trigger.siblings("ul");
            this.$items       = this.$menu.find("li");
            this.itemHeight   = parseInt(this.$items.height());
            this.itemStart    = 0;
            this.$moveup      = $("<li>").addClass("icon-chevron-up hide").prependTo(self.$menu);
            this.$movedown    = $("<li>").addClass("icon-chevron-down hide").appendTo(self.$menu);
            this.options      = options;

            this.init = function(){
                self.$trigger.on("click", function(e){
                    e.stopPropagation();
                    e.preventDefault();

                    self.isOpen() ? self.closeMenu(): self.openMenu();
                });

                self.$movedown.on("click", function(){
                    self.listDown();
                });

                self.$moveup.on("click", function(){
                    self.listUp();
                });

                $(window).resize(function(){
                    self.rescale();
                });
            };

            this.openMenu = function(){
                if( !$(".bt-overlay").length )
                    $("<div>").addClass("bt-overlay").appendTo("body").on("click", self.closeMenu);

                $("body").removeClass("bt-menu-close").addClass("bt-menu-open");
                self.rescale();
            };

            this.closeMenu = function(){
                $(".bt-overlay").remove();
                $("body").removeClass("bt-menu-open").addClass("bt-menu-close");
            };

            this.rescale = function(){

                var availableHeight = $(window).height() - self.$menu.offset().top,
                    availableCount = Math.ceil(availableHeight / self.itemHeight);

                if( self.itemStart ){
                    self.$moveup.removeClass("hide");
                    availableCount --;
                }else{
                    self.$moveup.addClass("hide");
                }

                if( self.$items.length * self.itemHeight > availableHeight && self.itemStart + 3 < availableCount ){
                    self.$movedown.removeClass("hide");
                    availableCount --;
                }else{
                    self.$movedown.addClass("hide");
                }

                $.each(self.$items, function(i){

                    if ( i < self.itemStart ||  i + 1 >= self.itemStart + availableCount ) {
                        $(this).css({display: "none"});
                    } else {
                        $(this).css({display: "block"});
                    }

                });
            };

            this.isOpen = function(){
                return $("body").hasClass("bt-menu-open");
            };

            this.listDown = function(){
                if( self.$items.length > self.itemStart) self.itemStart ++;
                self.rescale();
            };

            this.listUp = function(){
                if( self.itemStart ) self.itemStart --;
                self.rescale();
            };

            this.init();
        };


        tibiMenu.DEFAULTS = {

        };

        $.fn.colMenu = function(options) {
            options = $.extend({}, tibiMenu.DEFAULTS, options);
            return new tibiMenu(this, options);
        };

        $.fn.colMenu.Constructor = tibiMenu;

    })(jQuery);
})();