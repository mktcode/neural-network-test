(function($) {
    $.fn.drawToCanvas = function(options) {

        var settings = $.extend({
            zoom: 1
        }, options );

        var canvas = this[0];

        if (!canvas.getContext) {
            console.log('Error: no canvas.getContext!');

            return;
        }

        var context = canvas.getContext('2d');
        context.scale(settings.zoom, settings.zoom);
        context.lineWidth = 10;
        context.lineJoin = 'round';


        var tool = new Pencil();

        canvas.addEventListener('mousedown', position, false);
        canvas.addEventListener('mousemove', position, false);
        canvas.addEventListener('mouseup',   position, false);

        function Pencil() {
            var tool = this;
            this.started = false;

            // This is called when you start holding down the mouse button.
            // This starts the pencil drawing.
            this.mousedown = function (e) {
                context.beginPath();
                context.moveTo(e._x, e._y);
                tool.started = true;
            };

            // This function is called every time you move the mouse. Obviously, it only
            // draws if the tool.started state is set to true (when you are holding down
            // the mouse button).
            this.mousemove = function (e) {
                if (tool.started) {
                    context.lineTo(e._x, e._y);
                    context.stroke();
                }
            };

            // This is called when you release the mouse button.
            this.mouseup = function (e) {
                if (tool.started) {
                    tool.mousemove(e);
                    context.fillRect(e._x - 5, e._y - 5, 10, 10 );
                    tool.started = false;
                }
            };
        }

        function position(e) {
            var border = ($(e.target).outerWidth(false) - $(e.target).innerWidth()) / 2;
            if (e.layerX || e.layerX == 0) { // Firefox
                e._x = e.layerX - border;
                e._y = e.layerY - border;
            } else if (e.offsetX || e.offsetX == 0) { // Opera
                e._x = e.offsetX - border;
                e._y = e.offsetY - border;
            }

            // Call the event handler of the tool.
            var func = tool[e.type];
            if (func) {
                func(e);
            }
        }

        return this;
    };

}(jQuery));