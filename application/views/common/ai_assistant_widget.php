<?php defined('BASEPATH') or exit('No direct script access allowed');
$_aiUserName = htmlspecialchars($JwtData->User->Name ?? 'there', ENT_QUOTES, 'UTF-8');
$_aiOrgName  = htmlspecialchars($JwtData->Org->OrgName ?? '', ENT_QUOTES, 'UTF-8');
?>
<!-- ─── AI Business Assistant Widget ────────────────────────────────────── -->
<div id="aiAssistantWidget" aria-label="AI Business Assistant" role="complementary">

    <!-- Floating trigger button -->
    <button id="aiAssistantBtn" type="button" aria-label="Open AI assistant" aria-expanded="false" aria-controls="aiAssistantPanel">
        <span class="ai-btn-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 3H5C3.9 3 3 3.9 3 5V9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M15 3H19C20.1 3 21 3.9 21 5V9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M9 21H5C3.9 21 3 3.9 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M15 21H19C20.1 21 21 20.1 21 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                <path d="M12 7V9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M12 15V17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M7 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M15 12H17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="ai-btn-label">AI</span>
    </button>

    <!-- Chat panel -->
    <div id="aiAssistantPanel" role="dialog" aria-modal="false" aria-label="AI Business Assistant" hidden>

        <!-- Panel header -->
        <div class="ai-panel-header">
            <div class="ai-panel-title">
                <span class="ai-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="10" cy="10" r="3"/>
                        <path d="M10 4v2M10 14v2M4 10h2M14 10h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <div class="ai-panel-name">AI Assistant</div>
                    <div class="ai-panel-sub"><?php echo $_aiOrgName; ?></div>
                </div>
            </div>
            <button id="aiAssistantClose" type="button" class="ai-close-btn" aria-label="Close AI assistant">
                <i class="bx bx-x"></i>
            </button>
        </div>

        <!-- Message list -->
        <div id="aiMessageList" class="ai-message-list" role="log" aria-live="polite" aria-label="Conversation">
            <!-- Greeting injected by JS on first open -->
        </div>

        <!-- Typing indicator (hidden by default) -->
        <div id="aiTypingIndicator" class="ai-typing-wrap" hidden aria-label="AI is typing">
            <div class="ai-typing-dots">
                <span></span><span></span><span></span>
            </div>
        </div>

        <!-- Input area -->
        <div class="ai-input-area">
            <textarea
                id="aiMessageInput"
                class="ai-textarea"
                placeholder="Ask about sales, stock, receivables…"
                rows="1"
                maxlength="600"
                aria-label="Your message"
                autocomplete="off"
            ></textarea>
            <button id="aiSendBtn" type="button" class="ai-send-btn" aria-label="Send message" disabled>
                <i class="bx bx-send"></i>
            </button>
        </div>

        <div class="ai-panel-footer">
            Powered by Gemini Flash &nbsp;·&nbsp; Data is live from your ERP
        </div>

    </div>
</div>

<script>
window._aiUserName = '<?php echo addslashes($_aiUserName); ?>';
</script>
