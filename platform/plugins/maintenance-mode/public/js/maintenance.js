/******/ (() => { // webpackBootstrap
/*!******************************************************************************!*\
  !*** ./platform/plugins/maintenance-mode/resources/assets/js/maintenance.js ***!
  \******************************************************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var MaintenanceMode = /*#__PURE__*/function () {
  function MaintenanceMode() {
    _classCallCheck(this, MaintenanceMode);
  }
  return _createClass(MaintenanceMode, [{
    key: "init",
    value: function init() {
      $(document).on('click', '#btn-maintenance', function (event) {
        event.preventDefault();
        var _self = $(event.currentTarget);
        _self.addClass('button-loading');
        $.ajax({
          type: 'POST',
          url: route('system.maintenance.run'),
          cache: false,
          data: _self.closest('form').serialize(),
          success: function success(res) {
            if (!res.error) {
              Botble.showSuccess(res.message);
              var data = res.data;
              _self.text(data.message);
              if (!data.is_down) {
                _self.removeClass('btn-warning').addClass('btn-info');
                _self.closest('form').find('.maintenance-mode-notice div span').removeClass('text-danger').addClass('text-success').text(data.notice);
              } else {
                _self.addClass('btn-warning').removeClass('btn-info');
                _self.closest('form').find('.maintenance-mode-notice div span').addClass('text-danger').removeClass('text-success').text(data.notice);
                if (data.url) {
                  $('#bypassMaintenanceMode .maintenance-mode-bypass').attr('href', data.url);
                  $('#bypassMaintenanceMode #secret-link').val(data.url);
                  $('#bypassMaintenanceMode').modal('show');
                }
              }
            } else {
              Botble.showError(res.message);
            }
          },
          error: function error(res) {
            Botble.handleError(res);
          },
          complete: function complete() {
            _self.removeClass('button-loading');
          }
        });
      });
    }
  }]);
}();
$(document).ready(function () {
  new MaintenanceMode().init();
});
/******/ })()
;