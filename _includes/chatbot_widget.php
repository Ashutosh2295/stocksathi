<?php
/**
 * Floating chatbot widget – fully self-contained (CSS + JS inlined).
 * Zero external file dependencies – works on any shared hosting.
 */
if (!empty($GLOBALS['__STOCKSATHI_CHATBOT_RENDERED'])) {
    return;
}
$GLOBALS['__STOCKSATHI_CHATBOT_RENDERED'] = true;

require_once __DIR__ . '/chatbot_config.php';

if (!function_exists('_stocksathi_chatbot_base_path')) {
    function _stocksathi_chatbot_base_path() {
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }
        // Prefer URL path from DOCUMENT_ROOT → project root (fixes subfolder deploys where SCRIPT_NAME alone is wrong)
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
        $docRoot = rtrim($docRoot, '/');
        $projRoot = str_replace('\\', '/', dirname(__DIR__));
        $projRoot = rtrim($projRoot, '/');
        if ($docRoot !== '' && strpos($projRoot, $docRoot) === 0) {
            $base = substr($projRoot, strlen($docRoot));
            if ($base === '' || $base === '/') {
                return '';
            }
            return rtrim($base, '/');
        }
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
        $scriptDir  = dirname($scriptPath);
        if (stripos($scriptDir, '/landing1') !== false || stripos($scriptDir, '/pages') !== false) {
            $base = dirname($scriptDir);
        } else {
            $base = $scriptDir;
        }
        if ($base === '/' || $base === '\\' || $base === '.') {
            return '';
        }
        return rtrim($base, '/');
    }
}

if (!function_exists('_stocksathi_ss_msg_web_path')) {
    /** One endpoint: ss-msg.php (site root) → includes pages/api/chatbot-proxy.php → your Render API. */
    function _stocksathi_ss_msg_web_path($cbBase) {
        $prefix = ($cbBase === '' ? '' : '/' . trim($cbBase, '/'));
        return str_replace('//', '/', $prefix . '/ss-msg.php');
    }
}

$cbBase    = _stocksathi_chatbot_base_path();
$proxyPath = _stocksathi_ss_msg_web_path($cbBase);

$currentUrl  = $_SERVER['REQUEST_URI'];
$context = (
    stripos($currentUrl, '/landing') !== false ||
    $currentUrl === '/' ||
    $currentUrl === '/stocksathi/' ||
    (stripos($currentUrl, 'index.php') !== false && !isset($_SESSION['user_id']))
) ? 'landing' : 'internal';
?>
<style>
/* ===== StockSathi Chatbot Widget ===== */
#stocksathi-chatbot-root{position:fixed;right:25px;bottom:25px;z-index:99999;font-family:'Inter',system-ui,-apple-system,sans-serif;font-size:14px}
#stocksathi-chatbot-root .sscb-toggle{width:60px;height:60px;border-radius:50%;border:none;cursor:pointer;background:linear-gradient(135deg,#4f82d5 0%,#3a63a5 100%);color:#fff;box-shadow:0 10px 25px rgba(58,99,165,.4);display:flex;align-items:center;justify-content:center;transition:all .3s cubic-bezier(.4,0,.2,1)}
#stocksathi-chatbot-root .sscb-toggle:hover{transform:translateY(-5px) scale(1.05);box-shadow:0 15px 30px rgba(58,99,165,.5)}
#stocksathi-chatbot-root.sscb-open .sscb-toggle{display:none}
#stocksathi-chatbot-root .sscb-panel{display:none;flex-direction:column;width:min(400px,calc(100vw - 40px));height:min(580px,calc(100vh - 100px));background:#fff;border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,.15);overflow:hidden;border:1px solid rgba(0,0,0,.05);animation:sscb-slide-up .4s cubic-bezier(.4,0,.2,1)}
@keyframes sscb-slide-up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
#stocksathi-chatbot-root.sscb-open .sscb-panel{display:flex}
#stocksathi-chatbot-root .sscb-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:linear-gradient(135deg,#4f82d5 0%,#3a63a5 100%);color:#fff}
#stocksathi-chatbot-root .sscb-head strong{font-size:16px;font-weight:600}
#stocksathi-chatbot-root .sscb-close{background:rgba(255,255,255,.15);border:none;color:#fff;width:32px;height:32px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s}
#stocksathi-chatbot-root .sscb-close:hover{background:rgba(255,255,255,.25)}
#stocksathi-chatbot-root .sscb-messages{flex:1;overflow-y:auto;padding:20px;background:#f8fafc;display:flex;flex-direction:column}
#stocksathi-chatbot-root .sscb-bubble{max-width:85%;margin-bottom:12px;padding:12px 16px;border-radius:16px;line-height:1.5;font-size:14px}
#stocksathi-chatbot-root .sscb-bubble--user{margin-left:auto;background:#4f82d5;color:#fff;border-bottom-right-radius:4px;box-shadow:0 4px 10px rgba(79,130,213,.2)}
#stocksathi-chatbot-root .sscb-bubble--user p{margin:0;color:#fff!important}
#stocksathi-chatbot-root .sscb-bubble--bot{margin-right:auto;background:#fff;color:#1e293b;border:1px solid #e2e8f0;border-bottom-left-radius:4px;box-shadow:0 2px 5px rgba(0,0,0,.02)}
#stocksathi-chatbot-root .sscb-answer p{margin:0 0 10px}
#stocksathi-chatbot-root .sscb-answer p:last-child{margin-bottom:0}
#stocksathi-chatbot-root .sscb-nav-wrap{margin-top:12px}
#stocksathi-chatbot-root .sscb-nav-btn{display:inline-block;padding:10px 18px;background:#4f82d5;color:#fff!important;text-decoration:none;border-radius:10px;font-weight:600;font-size:13px;transition:background .2s}
#stocksathi-chatbot-root .sscb-nav-btn:hover{background:#3a63a5}
#stocksathi-chatbot-root .sscb-quick-replies{display:flex;flex-wrap:wrap;gap:8px;padding:12px 20px;border-top:1px solid #f1f5f9;background:#fff}
#stocksathi-chatbot-root .sscb-chip{border:1px solid #e2e8f0;background:#f8fafc;color:#475569;padding:8px 14px;border-radius:999px;font-size:12px;cursor:pointer;transition:all .2s;font-weight:500}
#stocksathi-chatbot-root .sscb-chip:hover{background:#4f82d5;color:#fff;border-color:#4f82d5}
#stocksathi-chatbot-root .sscb-foot{display:flex;gap:10px;padding:16px 20px;border-top:1px solid #f1f5f9;background:#fff}
#stocksathi-chatbot-root .sscb-input{flex:1;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;font-size:14px;outline:none;transition:border-color .2s;background:#f8fafc}
#stocksathi-chatbot-root .sscb-input:focus{border-color:#4f82d5;background:#fff}
#stocksathi-chatbot-root .sscb-send{border:none;background:#4f82d5;color:#fff;width:45px;height:45px;border-radius:12px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .2s}
#stocksathi-chatbot-root .sscb-send:hover{background:#3a63a5}
#stocksathi-chatbot-root .sscb-typing span{display:inline-block;width:6px;height:6px;margin:0 2px;background:#94a3b8;border-radius:50%;animation:sscb-bounce 1.2s infinite ease-in-out}
#stocksathi-chatbot-root .sscb-typing span:nth-child(2){animation-delay:.2s}
#stocksathi-chatbot-root .sscb-typing span:nth-child(3){animation-delay:.4s}
@keyframes sscb-bounce{0%,80%,100%{transform:scale(.6);opacity:.5}40%{transform:scale(1);opacity:1}}
</style>

<div id="stocksathi-chatbot-root" data-context="<?= $context ?>" aria-live="polite">
    <button type="button" class="sscb-toggle" title="Open StockSathi assistant" aria-label="Open chat">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
    </button>
    <div class="sscb-panel" role="dialog" aria-label="StockSathi AI Assistant">
        <div class="sscb-head">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:8px;height:8px;background:#4ade80;border-radius:50%;"></div>
                <strong>StockSathi AI Assistant</strong>
            </div>
            <button type="button" class="sscb-close" aria-label="Close chat">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="sscb-messages"></div>
        <div class="sscb-quick-replies"></div>
        <div class="sscb-foot">
            <input type="text" class="sscb-input" placeholder="How can I help you today?" autocomplete="off" maxlength="2000">
            <button type="button" class="sscb-send" title="Send message">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var proxyPath      = <?= json_encode($proxyPath) ?>;
    var appBase        = <?= json_encode($cbBase === '' ? '' : '/' . trim($cbBase, '/')) ?>;
    var contextType    = <?= json_encode($context) ?>;
    // Direct Render URL used as fallback when PHP proxy can't reach it (e.g. InfinityFree blocks outbound cURL)
    var renderOrigin   = <?= json_encode(stocksathi_chatbot_remote_origin()) ?>;
    var proxyFailed    = false; // set true after first proxy failure to skip proxy next time

    function proxyPostUrl() {
        return new URL(proxyPath, window.location.origin).href;
    }
    function directRenderUrl() {
        var ep = (contextType === 'landing') ? '/api/chat/landing' : '/api/chat/internal';
        return renderOrigin + ep;
    }

    var root       = document.getElementById('stocksathi-chatbot-root');
    if (!root) return;

    var toggle     = root.querySelector('.sscb-toggle');
    var messagesEl = root.querySelector('.sscb-messages');
    var input      = root.querySelector('.sscb-input');
    var sendBtn    = root.querySelector('.sscb-send');
    var closeBtn   = root.querySelector('.sscb-close');
    var quickWrap  = root.querySelector('.sscb-quick-replies');
    var chatHistory = [];

    /* ---- helpers ---- */
    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function formatAnswer(text) {
        if (!text) return '';
        return escapeHtml(text).replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    }
    function saveChatState() {
        try {
            sessionStorage.setItem('sscb_' + contextType, JSON.stringify({
                open: root.classList.contains('sscb-open'),
                html: messagesEl.innerHTML,
                history: chatHistory
            }));
        } catch(e) {}
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
            b.addEventListener('click', function () { input.value = label; sendMessage(); });
            quickWrap.appendChild(b);
        });
    }
    function showTyping() {
        if (document.getElementById('sscb-typing')) return;
        var div = document.createElement('div');
        div.id = 'sscb-typing';
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
            appendBubble('bot', '<p>' + escapeHtml((data && data.error) ? data.error : 'Something went wrong. Try again.') + '</p>');
            setQuickReplies([]);
            return;
        }
        var inner  = data.data || {};
        var answer = inner.answer || '';
        appendBubble('bot', '<div class="sscb-answer">' + formatAnswer(answer) + '</div>');
        chatHistory.push({ role: 'assistant', content: answer });
        saveChatState();
        if (inner.action_type === 'navigate' && inner.action_url) {
            var navBtn = document.createElement('div');
            navBtn.className = 'sscb-nav-wrap';
            var a = document.createElement('a');
            a.href = (appBase || '') + inner.action_url;
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
        function doFetch(url, body, isDirect) {
            var opts = {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            };
            if (!isDirect) {
                opts.credentials = 'same-origin';
                opts.redirect    = 'error';
            }
            return fetch(url, opts).then(function (r) {
                return r.text().then(function (body) {
                    var j = null;
                    try { j = body ? JSON.parse(body) : null; } catch (e) {}
                    if (j && typeof j === 'object') {
                        return r.ok ? j : { success: false, error: j.error || ('HTTP ' + r.status), _proxyFail: !isDirect };
                    }
                    return { success: false, error: r.ok ? 'Invalid response.' : ('HTTP ' + r.status), _proxyFail: !isDirect };
                });
            });
        }

        var proxyBody  = { type: contextType, message: text, history: chatHistory };
        var directBody = { message: text, history: chatHistory };

        var fetchChain;
        if (proxyFailed) {
            // Host blocks outbound cURL — go direct from browser
            fetchChain = doFetch(directRenderUrl(), directBody, true);
        } else {
            fetchChain = doFetch(proxyPostUrl(), proxyBody, false)
                .catch(function () {
                    // Network-level proxy failure → try direct
                    proxyFailed = true;
                    return doFetch(directRenderUrl(), directBody, true);
                })
                .then(function (result) {
                    // Proxy returned a connection error message → retry direct
                    if (!result.success && result._proxyFail) {
                        proxyFailed = true;
                        return doFetch(directRenderUrl(), directBody, true);
                    }
                    return result;
                });
        }
        fetchChain
        .catch(function () {
            return { success: false, error: 'Could not reach the AI server. Please try again.' };
        })
        .then(handleResponse);
    }

    /* ---- open / close ---- */
    toggle.addEventListener('click', function () {
        root.classList.add('sscb-open');
        toggle.style.display = 'none';
        if (messagesEl.children.length === 0) {
            appendBubble('bot', contextType === 'landing'
                ? '<p>Hi! Ask me anything about how StockSathi can help your business.</p>'
                : '<p>Hi! Ask me about invoices, stock tracking, or anything else in the system.</p>');
        }
        saveChatState();
    });
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            root.classList.remove('sscb-open');
            toggle.style.display = 'flex';
            saveChatState();
        });
    }
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    /* ---- restore session ---- */
    try {
        var savedRaw = sessionStorage.getItem('sscb_' + contextType);
        if (savedRaw) {
            var saved = JSON.parse(savedRaw);
            if (saved.html)    { messagesEl.innerHTML = saved.html; messagesEl.scrollTop = messagesEl.scrollHeight; }
            if (saved.history) { chatHistory = saved.history; }
            if (saved.open)    { root.classList.add('sscb-open'); toggle.style.display = 'none'; }
        }
    } catch(e) {}

    /* ---- silent wake-up ping (fires on page load, warms Render server) ---- */
    setTimeout(function () {
        try {
            // Try proxy first; if it errors, ping Render directly so it wakes up
            fetch(proxyPostUrl(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                redirect: 'error',
                body: JSON.stringify({ type: contextType, message: 'ping', history: [] })
            }).then(function (r) {
                return r.json().catch(function(){ return {}; });
            }).then(function (j) {
                // If proxy returned a curl/connection error, flag it and wake Render directly
                if (j && !j.success && j.error && (
                    j.error.indexOf('Could not connect') !== -1 ||
                    j.error.indexOf('block') !== -1 ||
                    j.error.indexOf('cURL') !== -1
                )) {
                    proxyFailed = true;
                    fetch(directRenderUrl(), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: 'ping', history: [] })
                    }).catch(function(){});
                }
            }).catch(function () {
                // Proxy unreachable — ping Render directly
                proxyFailed = true;
                fetch(directRenderUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: 'ping', history: [] })
                }).catch(function(){});
            });
        } catch (e) {}
    }, 1500);

})();
</script>
