<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Customer Profile Modal — Notes Tab
 *
 * @var array  $Notes        rows from getCustomerNotes()
 * @var object $JwtData
 * @var string $DateFormat
 * @var int    $CustomerUID
 *
 * Requires CustomerNotesTbl in the Customers schema — see DB migration note below.
 */
?>
<div class="p-3 p-md-4">

    <!-- Add Note Editor -->
    <div class="mb-3">
        <div class="cp-note-editor-wrap mb-2">
            <div id="cpNoteEditor"></div>
        </div>
        <div class="text-end">
            <button type="button" class="btn btn-sm btn-primary" id="cpSaveNoteBtn">
                <i class="bx bx-save me-1"></i>Add Note
            </button>
        </div>
    </div>

    <!-- Notes List -->
    <div id="cpNotesList">
        <?php if (empty($Notes)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted cp-note-empty" id="cpNoNotes">
            <i class="bx bx-note fs-2 mb-2"></i>
            No notes yet. Add the first note above.
        </div>
        <?php else: ?>
        <?php foreach ($Notes as $note):
            $words    = explode(' ', trim($note['UserName'] ?? 'U'));
            $initials = strtoupper(substr($words[0], 0, 1)) . strtoupper(substr($words[1] ?? '', 0, 1));
            $ago = '';
            if (!empty($note['CreatedOn'])) {
                $ts   = strtotime($note['CreatedOn']);
                $diff = time() - $ts;
                if ($diff < 60)        $ago = 'just now';
                elseif ($diff < 3600)  $ago = floor($diff / 60)  . 'm ago';
                elseif ($diff < 86400) $ago = floor($diff / 3600) . 'h ago';
                else                   $ago = date($DateFormat, $ts);
            }
        ?>
        <div class="d-flex gap-3 mb-3 pb-3 border-bottom align-items-start cp-note-item">
            <div class="avatar avatar-sm flex-shrink-0">
                <span class="avatar-initial rounded-circle bg-label-primary cp-note-avatar">
                    <?php echo htmlspecialchars($initials ?: 'U'); ?>
                </span>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold cp-note-user"><?php echo htmlspecialchars($note['UserName'] ?? 'Unknown'); ?></span>
                    <span class="text-muted cp-note-time" title="<?php echo htmlspecialchars($note['CreatedOn'] ?? ''); ?>">
                        <?php echo $ago; ?>
                    </span>
                </div>
                <div class="cp-note-html">
                    <?php echo strip_tags($note['Note'], '<p><br><strong><em><u><s><ol><ul><li><span><blockquote>'); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
(function () {
    'use strict';
    // Destroy any previous Quill instance (tab reload after save)
    if (window._cpNoteQuill) {
        try { window._cpNoteQuill = null; } catch (e) {}
    }

    window._cpNoteQuill = new Quill('#cpNoteEditor', {
        theme: 'snow',
        placeholder: 'Type an internal note about this customer…',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean']
            ]
        }
    });
})();
</script>

<?php
/*
 * ── DB Migration Required ─────────────────────────────────────────────────
 * Run this SQL once to enable the Notes tab:
 *
 * CREATE TABLE IF NOT EXISTS `Customers`.`CustomerNotesTbl` (
 *   `NoteUID`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
 *   `OrgUID`      INT UNSIGNED NOT NULL,
 *   `CustomerUID` INT UNSIGNED NOT NULL,
 *   `Note`        TEXT NOT NULL,
 *   `UserUID`     INT UNSIGNED NOT NULL,
 *   `IsDeleted`   TINYINT(1) NOT NULL DEFAULT 0,
 *   `CreatedOn`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   PRIMARY KEY (`NoteUID`),
 *   INDEX `idx_org_cust` (`OrgUID`, `CustomerUID`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 * ─────────────────────────────────────────────────────────────────────────
 */
?>
