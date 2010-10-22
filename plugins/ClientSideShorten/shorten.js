//wrap everything in a self-executing anonymous function to avoid conflicts
(function(){

    // smart(x) from Paul Irish
    // http://paulirish.com/2009/throttled-smartresize-jquery-event-handler/

    (function($,sr){

        // debouncing function from John Hann
        // http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
        var debounce = function (func, threshold, execAsap) {
            var timeout;

            return function debounced () {
                var obj = this, args = arguments;
                function delayed () {
                    if (!execAsap)
                        func.apply(obj, args);
                        timeout = null; 
                };

                if (timeout)
                    clearTimeout(timeout);
                else if (execAsap)
                    func.apply(obj, args);

                timeout = setTimeout(delayed, threshold || 100); 
            };
        }
        jQuery.fn[sr] = function(fn){  return fn ? this.bind('keypress', debounce(fn, 1000)) : this.trigger(sr); };

    })(jQuery,'smartkeypress');

    function shorten()
    {
        $noticeDataText = $('#'+SN.C.S.NoticeDataText);
        if(Notice_maxContent > 0 && $noticeDataText.val().length > Notice_maxContent){
            var original = $noticeDataText.val();
            shortenAjax = $.ajax({
                url: $('address .url')[0].href+'/plugins/ClientSideShorten/shorten',
                data: { text: $noticeDataText.val() },
                dataType: 'text',
                success: function(data) {
                    if(original == $noticeDataText.val()) {
                        $noticeDataText.val(data).keyup();
                    }
                }
            });
        }
    }

    $(document).ready(function(){
        $noticeDataText = $('#'+SN.C.S.NoticeDataText);
        $noticeDataText.smartkeypress(function(e){
            //if(typeof(shortenAjax) !== 'undefined') shortenAjax.abort();
            if(e.charCode == '32') {
                shorten();
            }
        });
        $noticeDataText.bind('paste', function() {
            //if(typeof(shortenAjax) !== 'undefined') shortenAjax.abort();
            setTimeout(shorten,1);
        });
    });

})();
