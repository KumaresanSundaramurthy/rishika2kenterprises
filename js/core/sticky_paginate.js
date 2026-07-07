/**
 * Sticky Pagination — auto-initialises for any container marked with
 * class `apex-sticky-pag` and attribute `data-static-pag` pointing to
 * the static pagination element.
 *
 * HTML usage:
 *   <div class="apex-sticky-pag" data-static-pag="#myPagination" style="display:none;">
 *       <div class="apex-sticky-pag-inner"></div>
 *   </div>
 *
 * To disable on a page: simply remove this <script> include.
 */
$(function () {
    $('.apex-sticky-pag').each(function () {
        var $sticky = $(this);
        var $static = $($sticky.data('static-pag'));
        var $inner  = $sticky.find('.apex-sticky-pag-inner');

        function _sync() {
            $inner.html($static.html());
        }

        function _toggle() {
            if (!$static.length) return;
            var r       = $static[0].getBoundingClientRect();
            var visible = r.top < $(window).height() && r.bottom > 0;
            if (visible) {
                $sticky.stop(true, true).fadeOut(150);
            } else {
                _sync();
                $sticky.stop(true, true).fadeIn(150);
            }
        }

        $(window).on('scroll resize', _toggle);
        _toggle();
    });
});
