<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Vendor Profile Modal — Notes Tab
 *
 * @var array  $Notes      rows from getVendorNotes()
 * @var object $JwtData
 * @var string $DateFormat
 * @var int    $VendorUID
 */
?>

<div class="p-3 p-md-4">

    <!-- ── Add Note ─────────────────────────────────────────────────────── -->
    <div class="mb-4">
        <div id="vpNoteEditor" class="cp-note-quill-wrap"></div>
        <div class="d-flex justify-content-end mt-2">
            <button type="button" class="btn btn-sm btn-warning" id="vpNoteSaveBtn">
                <i class="bx bx-save me-1"></i>Save Note
            </button>
        </div>
    </div>

    <!-- ── Existing Notes ────────────────────────────────────────────────── -->
    <div id="vpNotesList">
        <?php if (empty($Notes)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" id="vpNotesEmpty">
            <i class="bx bx-note fs-2 mb-2"></i>
            <span>No notes yet. Add the first one above.</span>
        </div>
        <?php else: ?>
        <?php foreach ($Notes as $note):
            $authorName = htmlspecialchars(trim(($note['FirstName'] ?? '') . ' ' . ($note['LastName'] ?? '')) ?: 'Unknown');
            $noteDate   = !empty($note['CreatedDate']) ? date($DateFormat, strtotime($note['CreatedDate'])) : '';
        ?>
        <div class="cp-note-card mb-3">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="avatar avatar-xs">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                        <?php echo strtoupper(substr($note['FirstName'] ?? '?', 0, 1)); ?>
                    </span>
                </div>
                <span class="fw-semibold cp-note-author"><?php echo $authorName; ?></span>
                <span class="text-muted cp-note-date"><?php echo $noteDate; ?></span>
            </div>
            <div class="cp-note-body ql-editor">
                <?php echo $note['NoteContent'] ?? ''; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    // ── Init Quill ─────────────────────────────────────────────────────────
    window._vpNoteQuill = new Quill('#vpNoteEditor', {
        theme      : 'snow',
        placeholder: 'Add a note about this vendor…',
        modules    : {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean']
            ]
        }
    });
})();
</script>
