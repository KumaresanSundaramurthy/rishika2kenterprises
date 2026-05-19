<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (empty($signatures)): ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <div style="width:72px;height:72px;background:#f0f4ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
                <i class="bx bx-pen" style="font-size:2rem;color:#696cff;"></i>
            </div>
        </div>
        <h6 class="text-muted mb-1">No signatures yet</h6>
        <p class="text-muted small mb-3">Add a signature by uploading an image or drawing it on a canvas.</p>
        <button class="btn btn-primary btn-sm" id="btnAddSigEmpty">
            <i class="bx bx-plus me-1"></i>Add Signature
        </button>
    </div>
<?php else: ?>
    <div class="row g-3 p-3">
        <?php
            $cdnBase = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
        ?>
        <?php foreach ($signatures as $sig):
            $isDefault = (int)($sig->IsDefault ?? 0) === 1;
            $isDraw    = ($sig->SignatureType ?? '') === 'Draw';
            $imgSrc    = $isDraw
                ? ($sig->DrawData ?? '')
                : ($cdnBase . ($sig->ImagePath ?? ''));
        ?>
        <div class="col-xl-4 col-md-6" id="sigCard_<?php echo (int)$sig->SignatureUID; ?>">
            <div class="card h-100 <?php echo $isDefault ? 'border-primary' : ''; ?>"
                 style="<?php echo $isDefault ? 'border-width:2px;' : ''; ?>">

                <!-- Signature Preview -->
                <div class="card-body pb-2" style="background:#f8f9fc;border-radius:8px 8px 0 0;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge <?php echo $isDraw ? 'bg-label-info' : 'bg-label-secondary'; ?>" style="font-size:0.68rem;">
                                <i class="bx <?php echo $isDraw ? 'bx-pencil' : 'bx-image'; ?> me-1"></i>
                                <?php echo $isDraw ? 'Drawn' : 'Uploaded'; ?>
                            </span>
                            <?php if ($isDefault): ?>
                            <span class="badge bg-label-primary" style="font-size:0.68rem;">
                                <i class="bx bx-star me-1"></i>Default
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <?php if (!$isDefault): ?>
                            <button class="btn btn-icon btn-sm text-warning setDefaultSigBtn"
                                    data-uid="<?php echo (int)$sig->SignatureUID; ?>"
                                    title="Set as default">
                                <i class="bx bx-star"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-icon btn-sm text-primary editSigBtn"
                                    data-uid="<?php echo (int)$sig->SignatureUID; ?>"
                                    data-label="<?php echo htmlspecialchars($sig->Label ?? 'My Signature'); ?>"
                                    data-type="<?php echo htmlspecialchars($sig->SignatureType ?? 'Upload'); ?>"
                                    data-imgsrc="<?php echo htmlspecialchars($imgSrc); ?>"
                                    title="Edit">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button class="btn btn-icon btn-sm text-danger deleteSigBtn"
                                    data-uid="<?php echo (int)$sig->SignatureUID; ?>"
                                    data-label="<?php echo htmlspecialchars($sig->Label ?? 'this signature'); ?>"
                                    title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Signature image preview -->
                    <div class="d-flex align-items-center justify-content-center"
                         style="min-height:80px;background:#fff;border:1px dashed #d0d5dd;border-radius:6px;padding:8px;">
                        <?php if (!empty($imgSrc)): ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                             alt="Signature"
                             style="max-width:100%;max-height:80px;object-fit:contain;" />
                        <?php else: ?>
                        <span class="text-muted small"><i class="bx bx-image-alt me-1"></i>No preview</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="card-footer bg-transparent py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold" style="font-size:0.875rem;">
                                <?php echo htmlspecialchars($sig->Label ?? 'My Signature'); ?>
                            </div>
                            <div class="text-muted" style="font-size:0.72rem;">
                                <?php
                                    $dt = new DateTime($sig->CreatedOn ?? 'now');
                                    echo $dt->format('d M Y');
                                ?>
                                <?php if (!empty($sig->FileSize)): ?>
                                &nbsp;&bull;&nbsp;<?php echo round($sig->FileSize / 1024, 1); ?> KB
                                <?php endif; ?>
                                <?php if (!empty($sig->Width) && !empty($sig->Height)): ?>
                                &nbsp;&bull;&nbsp;<?php echo $sig->Width; ?>&times;<?php echo $sig->Height; ?>px
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isDefault): ?>
                        <span class="text-primary small"><i class="bx bx-star-filled me-1"></i>Default</span>
                        <?php else: ?>
                        <button class="btn btn-sm btn-link text-muted p-0 setDefaultSigBtn"
                                data-uid="<?php echo (int)$sig->SignatureUID; ?>"
                                title="Set as default">
                            <i class="bx bx-star me-1"></i>
                            <span style="font-size:0.78rem;">Set Default</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
