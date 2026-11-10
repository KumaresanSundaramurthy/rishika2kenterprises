/**
 * AI Business Assistant widget.
 * Communicates with POST api/assistant/chat via global_services_url.
 */
(function () {
    'use strict';

    var _btn       = document.getElementById('aiAssistantBtn');
    var _panel     = document.getElementById('aiAssistantPanel');
    var _closeBtn  = document.getElementById('aiAssistantClose');
    var _msgList   = document.getElementById('aiMessageList');
    var _input     = document.getElementById('aiMessageInput');
    var _sendBtn   = document.getElementById('aiSendBtn');
    var _typing    = document.getElementById('aiTypingIndicator');

    if (!_btn || !_panel) return;

    /** @type {Array<{role: string, text: string}>} */
    var _history   = [];
    var _open      = false;
    var _pending   = false;
    var _greeted   = false;

    // ── Toggle panel ──────────────────────────────────────────────────────────

    /**
     * @param {boolean} show
     * @returns {void}
     */
    function _setOpen(show) {
        _open = show;
        if (show) {
            _panel.hidden = false;
            _btn.setAttribute('aria-expanded', 'true');
            _btn.classList.add('ai-btn-active');
            _panel.classList.add('ai-panel-visible');
            if (!_greeted) { _showGreeting(); _greeted = true; }
            setTimeout(function () { _input.focus(); }, 50);
        } else {
            _panel.classList.remove('ai-panel-visible');
            _btn.setAttribute('aria-expanded', 'false');
            _btn.classList.remove('ai-btn-active');
            setTimeout(function () { _panel.hidden = true; }, 260);
        }
    }

    $(_btn).on('click', function () { _setOpen(!_open); });
    $(_closeBtn).on('click', function () { _setOpen(false); });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && _open) { _setOpen(false); }
    });

    // ── Greeting ──────────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _showGreeting() {
        var name = (typeof window._aiUserName !== 'undefined') ? window._aiUserName : '';
        var greet = name ? 'Hi ' + name + '! ' : 'Hello! ';
        _addBubble(
            'model',
            greet + 'I can answer questions about your business data — sales, receivables, stock, top customers, and more. What would you like to know?'
        );
    }

    // ── Textarea auto-resize + send enable ────────────────────────────────────

    $(_input).on('input', function () {
        _input.style.height = 'auto';
        _input.style.height = Math.min(_input.scrollHeight, 120) + 'px';
        $(_sendBtn).prop('disabled', (_input.value.trim().length === 0) || _pending);
    });

    $(_input).on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!$(_sendBtn).prop('disabled')) { _doSend(); }
        }
    });

    $(_sendBtn).on('click', _doSend);

    // ── Send message ──────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _doSend() {
        var msg = _input.value.trim();
        if (!msg || _pending) return;

        _addBubble('user', msg);
        _input.value = '';
        _input.style.height = 'auto';
        $(_sendBtn).prop('disabled', true);
        _pending = true;
        _typing.hidden = false;
        _scrollToBottom();

        $.ajax({
            url     : global_services_url + 'api/assistant/chat',
            method  : 'POST',
            data    : { message: msg, history: JSON.stringify(_history) },
            dataType: 'json',
            success : function (d) {
                _typing.hidden = true;
                _pending = false;
                if (d.Error) {
                    _addBubble('model', 'Sorry, I ran into a problem: ' + (d.Message || 'unknown error.'));
                } else {
                    _addBubble('model', d.Reply || 'No response received.');
                    _history.push({ role: 'user',  text: msg     });
                    _history.push({ role: 'model', text: d.Reply });
                    if (_history.length > 12) { _history = _history.slice(-12); }
                }
                _input.focus();
                $(_sendBtn).prop('disabled', _input.value.trim().length === 0);
            },
            error   : function () {
                _typing.hidden = true;
                _pending = false;
                _addBubble('model', 'Network error. Please check your connection and try again.');
                $(_sendBtn).prop('disabled', _input.value.trim().length === 0);
            }
        });
    }

    // ── Render helpers ────────────────────────────────────────────────────────

    /**
     * @param {'user'|'model'} role
     * @param {string}         text
     * @returns {void}
     */
    function _addBubble(role, text) {
        var wrap = document.createElement('div');
        wrap.className = 'ai-bubble-wrap ai-bubble-' + role;

        if (role === 'model') {
            var avatar = document.createElement('div');
            avatar.className = 'ai-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor"><circle cx="10" cy="10" r="3"/><path d="M10 4v2M10 14v2M4 10h2M14 10h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
            wrap.appendChild(avatar);
        }

        var bubble = document.createElement('div');
        bubble.className = 'ai-bubble';
        // Render newlines as <br>, sanitise everything else
        bubble.innerHTML = _escHtml(text).replace(/\n/g, '<br>');
        wrap.appendChild(bubble);

        _msgList.appendChild(wrap);
        _scrollToBottom();
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _escHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * @returns {void}
     */
    function _scrollToBottom() {
        requestAnimationFrame(function () {
            _msgList.scrollTop = _msgList.scrollHeight;
        });
    }

}());
