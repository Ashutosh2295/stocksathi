/**
 * StockSathi chatbot — same-origin POST to ss-msg.php (or set StocksathiChatbotConfig.proxyUrl).
 */
(function () {
    'use strict';

    var cfg = window.StocksathiChatbotConfig || {};
    var proxyPath = cfg.proxyPath || '/ss-msg.php';
    var appBase = cfg.appBase || '';
    if (cfg.proxyUrl) {
        proxyPath = cfg.proxyUrl;
    }

    function postUrl() {
        if (proxyPath.indexOf('http') === 0) return proxyPath;
        return new URL(proxyPath, window.location.origin).href;
    }

    var root = document.getElementById('stocksathi-chatbot-root');
    var contextType = cfg.context || (root && root.getAttribute('data-context')) || 'internal';

    if (!root || !proxyPath) return;

    var toggle = root.querySelector('.sscb-toggle');
    var messagesEl = root.querySelector('.sscb-messages');
    var input = root.querySelector('.sscb-input');
    var sendBtn = root.querySelector('.sscb-send');
    var quickWrap = root.querySelector('.sscb-quick-replies');

    var chatHistory = [];

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function formatAnswer(text) {
        if (!text) return '';
        var esc = escapeHtml(text);
        return esc.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    }

    function saveChatState() {
        var state = {
            open: root.classList.contains('sscb-open'),
            html: messagesEl.innerHTML,
            history: chatHistory
        };
        sessionStorage.setItem('sscb_persist_' + contextType, JSON.stringify(state));
    }

    function appendBubble(role, html) {
        var div = document.createElement('div');
        div.className = 'sscb-bubble sscb-bubble--' + role;
        div.innerHTML = html;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        saveChatState();
    }

    function setQuickReplies(list) {
        quickWrap.innerHTML = '';
        if (!list || !list.length) return;
        list.forEach(function (label) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'sscb-chip';
            b.textContent = label;
            b.addEventListener('click', function () {
                input.value = label;
                sendMessage();
            });
            quickWrap.appendChild(b);
        });
    }

    function showTyping() {
        var id = 'sscb-typing';
        if (document.getElementById(id)) return;
        var div = document.createElement('div');
        div.id = id;
        div.className = 'sscb-bubble sscb-bubble--bot sscb-typing';
        div.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideTyping() {
        var t = document.getElementById('sscb-typing');
        if (t) t.remove();
    }

    function handleResponse(data) {
        hideTyping();
        if (!data || !data.success) {
            appendBubble('bot', '<p>' + escapeHtml(data && data.error ? data.error : 'Something went wrong. Try again.') + '</p>');
            setQuickReplies([]);
            return;
        }
        var inner = data.data || {};
        var answer = inner.answer || '';
        appendBubble('bot', '<div class="sscb-answer">' + formatAnswer(answer) + '</div>');

        chatHistory.push({ role: 'assistant', content: answer });
        saveChatState();

        if (inner.action_type === 'navigate' && inner.action_url) {
            var navBtn = document.createElement('div');
            navBtn.className = 'sscb-nav-wrap';
            var a = document.createElement('a');
            a.href = appBase + inner.action_url;
            a.className = 'sscb-nav-btn';
            a.textContent = 'Open in app';
            navBtn.appendChild(a);
            messagesEl.lastChild.appendChild(navBtn);
        }

        setQuickReplies(inner.quick_replies || []);
    }

    function sendMessage() {
        var text = (input.value || '').trim();
        if (!text) return;
        input.value = '';
        appendBubble('user', '<p>' + escapeHtml(text) + '</p>');
        chatHistory.push({ role: 'user', content: text });
        saveChatState();
        setQuickReplies([]);
        showTyping();

        fetch(postUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            redirect: 'error',
            body: JSON.stringify({ type: contextType, message: text, history: chatHistory })
        })
            .then(function (r) {
                return r.text().then(function (body) {
                    var j = null;
                    try { j = body ? JSON.parse(body) : null; } catch (e) {}
                    if (j && typeof j === 'object') {
                        return r.ok ? j : { success: false, error: j.error || ('HTTP ' + r.status) };
                    }
                    return { success: false, error: 'Bad response from server.' };
                });
            })
            .catch(function () {
                return { success: false, error: 'Network error — check ss-msg.php is in site root.' };
            })
            .then(handleResponse);
    }

    toggle.addEventListener('click', function () {
        root.classList.toggle('sscb-open');
        toggle.style.display = 'none';
        if (root.classList.contains('sscb-open') && messagesEl.children.length === 0) {
            var welcomeText = contextType === 'landing'
                ? '<p>Hi! Ask me anything about how StockSathi can help your business.</p>'
                : '<p>Hi! Ask me about invoices, stock tracking, or anything else in the system.</p>';
            appendBubble('bot', welcomeText);
        }
        saveChatState();
    });

    var closeBtn = root.querySelector('.sscb-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            root.classList.remove('sscb-open');
            toggle.style.display = 'flex';
            saveChatState();
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    try {
        var savedRaw = sessionStorage.getItem('sscb_persist_' + contextType);
        if (savedRaw) {
            var saved = JSON.parse(savedRaw);
            if (saved.html) {
                messagesEl.innerHTML = saved.html;
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }
            if (saved.history && Array.isArray(saved.history)) {
                chatHistory = saved.history;
            }
            if (saved.open) {
                root.classList.add('sscb-open');
                toggle.style.display = 'none';
            }
        }
    } catch (err) {}
})();
