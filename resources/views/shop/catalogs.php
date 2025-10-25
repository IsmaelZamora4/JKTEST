<?php
require_once BASE_PATH . 'config/config.php';

// Helper para versionar assets
function asset_url_with_v($path)
{
    if (!$path) return '';
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^https?://#i', $path)) return $path;
    $fs = __DIR__ . '/' . ltrim($path, '/');
    return file_exists($fs) ? $path : $path;
}

// Directorios soportados para catálogos
$catalog_dirs = [
    'assets/catalogs',
    'assets/catalogos',
];

// Recolectar PDFs
$catalogs = [];
foreach ($catalog_dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = glob($dir . '/*.pdf');
    foreach ($files as $file) {
        $rel = str_replace(__DIR__ . '/', '', $file);
        $catalogs[] = [
            'path' => $rel,
            'name' => basename($file),
            'mtime' => filemtime($file),
            'size' => filesize($file),
            'url'  => asset_url_with_v($rel),
        ];
    }
}
// Ordenar por fecha (reciente primero)
usort($catalogs, function ($a, $b) {
    return $b['mtime'] <=> $a['mtime'];
});

// Helpers
function human_filesize($bytes, $decimals = 1)
{
    $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogos PDF - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Descarga nuestros catálogos en PDF de productos textiles y personalización.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
    <link href="assets/css/catalogs.css" rel="stylesheet">
    
</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>

    <section class="py-4 page-header-minimal">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="breadcrumb-link"><i class="fas fa-home me-1"></i>Inicio</a></li>
                    <li class="breadcrumb-item active breadcrumb-current" aria-current="page">Catálogos</li>
                </ol>
            </nav>
            <h1 class="mb-2">Catálogos PDF</h1>
            <p class="text-muted mb-0">Descarga nuestros catálogos de productos textiles y servicios de personalización.</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <?php if (empty($catalogs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No hay catálogos disponibles por el momento. Sube tus PDFs a assets/catalogs o assets/catalogos.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($catalogs as $c): ?>
                        <div class="col-lg-6">
                            <div class="p-3 d-flex align-items-center catalog-card rounded" data-url="<?php echo htmlspecialchars($c['url']); ?>" data-name="<?php echo htmlspecialchars($c['name']); ?>">
                                <div class="pdf-icon flex-shrink-0"><i class="fa-solid fa-file-pdf fa-2x"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></div>
                                    <div class="text-muted small">
                                        <?php echo date('Y-m-d H:i', $c['mtime']); ?> · <?php echo human_filesize($c['size']); ?>
                                    </div>
                                    <div class="mt-2 d-flex gap-2 align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary preview-btn" data-url="<?php echo htmlspecialchars($c['url']); ?>" title="Vista rápida">
                                            <i class="fa-solid fa-eye me-1"></i>Vista rápida
                                        </button>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($c['url']); ?>" target="_blank" rel="noopener">
                                            <i class="fa-solid fa-expand me-1"></i>Abrir
                                        </a>
                                        <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($c['url']); ?>" download>
                                            <i class="fa-solid fa-download me-1"></i>Descargar
                                        </a>
                                    </div>
                                </div>
                                <div class="ms-3 d-none d-lg-block catalog-hover-preview" aria-hidden="true"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include COMPONENT_PATH . 'footer.php'; ?>
    
        <!-- Preview Modal -->
            <div class="modal fade" id="catalogPreviewModal" tabindex="-1" aria-labelledby="catalogPreviewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="catalogPreviewModalLabel">Vista Rápida</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0" style="min-height:60vh; display:flex; flex-direction:column;">
                            <!-- Viewer toolbar -->
                                        <div class="d-flex align-items-center justify-content-between p-2 bg-light">
                                            <div></div>
                                            <div class="text-muted small">Páginas: <span id="pdfPageIndicator">0 / 0</span></div>
                                            <div>
                                                <a id="catalogPreviewDownload" class="btn btn-sm btn-primary me-2" href="#" download><i class="fa-solid fa-download me-1"></i>Descargar</a>
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                        <!-- Two-page viewer -->
                                        <div id="pdfViewerContainer" style="position:relative; display:flex; gap:12px; padding:12px; align-items:flex-start; justify-content:center; flex:1; overflow:auto; background:#f8f9fa;">
                                            <button class="pdf-nav-btn pdf-nav-left" id="pdfNavLeft" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                                            <div class="pdf-page-wrap" style="flex:0 0 48%; display:flex; align-items:center; justify-content:center; position:relative;">
                                                <canvas id="pdfCanvasLeft" style="width:100%; height:auto; border-radius:6px; background:#fff;"></canvas>
                                            </div>
                                            <div class="pdf-page-wrap" style="flex:0 0 48%; display:flex; align-items:center; justify-content:center; position:relative;">
                                                <canvas id="pdfCanvasRight" style="width:100%; height:auto; border-radius:6px; background:#fff;"></canvas>
                                            </div>
                                            <button class="pdf-nav-btn pdf-nav-right" id="pdfNavRight" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                                        </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Small hover overlay (reused) -->
        <div id="catalogHoverOverlay" class="catalog-hover-overlay d-none" aria-hidden="true"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- PDF.js from CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
                (function(){
                // Reveal animation
                const reveals = document.querySelectorAll('.catalog-card');
                        if('IntersectionObserver' in window){
                                const io = new IntersectionObserver((entries)=>{ entries.forEach(en=>{ if(en.isIntersecting){ en.target.classList.add('is-visible'); io.unobserve(en.target); } }); },{threshold:0.06});
                                reveals.forEach(r=>{ r.classList.add('reveal'); io.observe(r); });
                        } else { reveals.forEach(r=> r.classList.add('is-visible')); }

                        const previewModalEl = document.getElementById('catalogPreviewModal');
                const previewDownload = document.getElementById('catalogPreviewDownload');
                const bsModal = new bootstrap.Modal(previewModalEl);

                // PDF.js viewer state
                let pdfDoc = null;
                let currentPage = 1; // left page index
                const canvasLeft = document.getElementById('pdfCanvasLeft');
                const canvasRight = document.getElementById('pdfCanvasRight');
                const ctxLeft = canvasLeft.getContext('2d');
                const ctxRight = canvasRight.getContext('2d');
                const pageIndicator = document.getElementById('pdfPageIndicator');
                const pdfViewerContainer = document.getElementById('pdfViewerContainer');

                // PDF.js worker setup (use CDN worker)
                if(window['pdfjsLib']){
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                        // Render helper: render two pages (page and page+1)
                        async function renderSpread(pageNum){
                            if(!pdfDoc) return;
                            const total = pdfDoc.numPages;
                            // clamp
                            if(pageNum < 1) pageNum = 1;
                            if(pageNum > total) pageNum = total;
                            currentPage = pageNum;

                            // left page
                            try{
                                const page = await pdfDoc.getPage(pageNum);
                                const viewport = page.getViewport({scale: 1});
                                // compute scale to fit half container width
                                const containerWidth = (pdfViewerContainer.clientWidth - 24) / 2; // gap and padding
                                const containerHeight = pdfViewerContainer.clientHeight - 24; // padding
                                let scale = (containerWidth) / viewport.width;
                                // ensure height fits
                                if(viewport.height * scale > containerHeight){
                                    scale = containerHeight / viewport.height;
                                }
                                const vp = page.getViewport({scale});
                                canvasLeft.width = Math.floor(vp.width);
                                canvasLeft.height = Math.floor(vp.height);
                                canvasLeft.style.height = vp.height + 'px';
                                canvasLeft.style.width = vp.width + 'px';
                                const renderContext = { canvasContext: ctxLeft, viewport: vp };
                                await page.render(renderContext).promise;
                            }catch(err){ console.error('Error rendering left page', err); }

                            // right page
                            const rightPageNum = pageNum + 1;
                            if(rightPageNum <= pdfDoc.numPages){
                                try{
                                    const pageR = await pdfDoc.getPage(rightPageNum);
                                    const viewportR = pageR.getViewport({scale: 1});
                                    // use same scale as left for consistent spread
                                    let scaleR = (canvasLeft.width) / viewportR.width;
                                    const vpR = pageR.getViewport({scale: scaleR});
                                    canvasRight.width = Math.floor(vpR.width);
                                    canvasRight.height = Math.floor(vpR.height);
                                    canvasRight.style.height = vpR.height + 'px';
                                    canvasRight.style.width = vpR.width + 'px';
                                    const renderContextR = { canvasContext: ctxRight, viewport: vpR };
                                    await pageR.render(renderContextR).promise;
                                    canvasRight.style.display = '';
                                }catch(err){ console.error('Error rendering right page', err); canvasRight.style.display = 'none'; }
                            } else {
                                // hide right canvas when there's no next page
                                canvasRight.style.display = 'none';
                            }

                            // update indicator
                            pageIndicator.textContent = Math.min(pageNum+1, pdfDoc.numPages) + ' / ' + pdfDoc.numPages;
                            // update next/prev disabled states
                            document.getElementById('pdfPrev').disabled = (pageNum <= 1);
                            document.getElementById('pdfNext').disabled = (pageNum+1 >= pdfDoc.numPages);
                        }

                        // Helper to animate turn: capture overlays, animate out, render new spread, animate in
                        async function animateTurn(targetPage){
                            if(!pdfDoc) return;
                            // clamp
                            targetPage = Math.max(1, Math.min(targetPage, pdfDoc.numPages));
                            if(targetPage === currentPage) return;

                            // disable nav during animation
                            setNavDisabled(true);

                            // create overlay images from current canvases
                            const overlay = document.createElement('div');
                            overlay.className = 'page-overlay';
                            const leftImg = document.createElement('img');
                            const rightImg = document.createElement('img');
                            try{ leftImg.src = canvasLeft.toDataURL(); }catch(e){ leftImg.src = ''; }
                            try{ rightImg.src = canvasRight.style.display === 'none' ? '' : canvasRight.toDataURL(); }catch(e){ rightImg.src = ''; }
                            leftImg.style.width = canvasLeft.style.width || canvasLeft.width + 'px';
                            leftImg.style.height = canvasLeft.style.height || canvasLeft.height + 'px';
                            rightImg.style.width = canvasRight.style.width || canvasRight.width + 'px';
                            rightImg.style.height = canvasRight.style.height || canvasRight.height + 'px';
                            overlay.appendChild(leftImg);
                            overlay.appendChild(rightImg);
                            pdfViewerContainer.appendChild(overlay);

                            // prepare canvases to be hidden and set starting transforms for entrance
                            canvasLeft.classList.add('canvas-slide-in-left');
                            canvasRight.classList.add('canvas-slide-in-right');
                            canvasLeft.style.opacity = 0;
                            canvasRight.style.opacity = 0;

                            // animate overlays out
                            // determine direction
                            const dir = (targetPage > currentPage) ? 'next' : 'prev';
                            if(dir === 'next'){
                                // move overlays left
                                setTimeout(()=>{ leftImg.classList.add('anim-out-left'); rightImg.classList.add('anim-out-left'); }, 10);
                            } else {
                                setTimeout(()=>{ leftImg.classList.add('anim-out-right'); rightImg.classList.add('anim-out-right'); }, 10);
                            }

                            // wait for overlay animation (~520ms)
                            await new Promise(r => setTimeout(r, 540));

                            // render target spread
                            await renderSpread(targetPage);

                            // animate canvases in from opposite side
                            if(dir === 'next'){
                                // animate canvases from right -> center
                                canvasLeft.classList.remove('canvas-slide-in-left');
                                canvasLeft.style.transform = 'translateX(120%)';
                                canvasLeft.style.opacity = 0;
                                canvasRight.classList.remove('canvas-slide-in-right');
                                canvasRight.style.transform = 'translateX(120%)';
                                canvasRight.style.opacity = 0;
                                // force reflow
                                void canvasLeft.offsetWidth;
                                canvasLeft.style.transition = 'transform 520ms cubic-bezier(.2,.9,.2,1), opacity 320ms ease';
                                canvasRight.style.transition = 'transform 520ms cubic-bezier(.2,.9,.2,1), opacity 320ms ease';
                                canvasLeft.style.transform = 'translateX(0)'; canvasLeft.style.opacity = 1;
                                canvasRight.style.transform = 'translateX(0)'; canvasRight.style.opacity = 1;
                            } else {
                                // animate canvases from left -> center
                                canvasLeft.classList.remove('canvas-slide-in-left');
                                canvasLeft.style.transform = 'translateX(-120%)';
                                canvasLeft.style.opacity = 0;
                                canvasRight.classList.remove('canvas-slide-in-right');
                                canvasRight.style.transform = 'translateX(-120%)';
                                canvasRight.style.opacity = 0;
                                void canvasLeft.offsetWidth;
                                canvasLeft.style.transition = 'transform 520ms cubic-bezier(.2,.9,.2,1), opacity 320ms ease';
                                canvasRight.style.transition = 'transform 520ms cubic-bezier(.2,.9,.2,1), opacity 320ms ease';
                                canvasLeft.style.transform = 'translateX(0)'; canvasLeft.style.opacity = 1;
                                canvasRight.style.transform = 'translateX(0)'; canvasRight.style.opacity = 1;
                            }

                            // wait for canvas animation
                            await new Promise(r => setTimeout(r, 540));

                            // remove overlay
                            if(overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);

                            // cleanup inline transition styles to keep CSS controlled
                            canvasLeft.style.transition = ''; canvasRight.style.transition = '';
                            canvasLeft.style.transform = ''; canvasRight.style.transform = '';

                            // enable nav
                            setNavDisabled(false);
                        }

                        // wrapper to start modal and load PDF
                        document.querySelectorAll('.preview-btn').forEach(btn=>{
                            btn.addEventListener('click', function(){
                                const url = this.dataset.url;
                                previewDownload.href = url;
                                if(window['pdfjsLib']){
                                    ctxLeft.clearRect(0,0,canvasLeft.width, canvasLeft.height);
                                    ctxRight.clearRect(0,0,canvasRight.width, canvasRight.height);
                                    canvasRight.style.display = '';
                                    pdfjsLib.getDocument(url).promise.then(function(pdf){
                                        pdfDoc = pdf;
                                        // load first spread
                                        renderSpread(1);
                                    }).catch(function(err){ console.error('PDF.js failed to load PDF', err); window.open(url,'_blank'); });
                                } else { window.open(url, '_blank'); }
                                bsModal.show();
                            });
                        });

                        // Hover mini preview for desktop
                        const overlay = document.getElementById('catalogHoverOverlay');
                        document.querySelectorAll('.catalog-card').forEach(card=>{
                                const hoverBox = card.querySelector('.catalog-hover-preview');
                                if(!hoverBox) return;
                                const url = card.dataset.url;
                                let timer = null;
                                card.addEventListener('mouseenter', function(e){
                                    if(window.innerWidth < 992) return; // only desktop
                                    // show a lightweight skeleton immediately to avoid a black flash
                                    hoverBox.innerHTML = '<div class="catalog-skel"><div class="skeleton-spinner"></div></div>';
                                    // keep small delay before starting to load iframe
                                    timer = setTimeout(()=>{
                                        // create iframe and wait for it to load before showing
                                        const iframe = document.createElement('iframe');
                                        iframe.setAttribute('frameborder','0');
                                        iframe.style.width = '100%';
                                        iframe.style.height = '100%';
                                        // set src after attaching onload to detect when ready
                                        iframe.onload = function(){
                                            // replace skeleton with the loaded iframe
                                            hoverBox.innerHTML = '';
                                            hoverBox.appendChild(iframe);
                                            hoverBox.classList.add('visible');
                                        };
                                        iframe.src = url + '#page=1&zoom=page-width';
                                    }, 220);
                                });
                                card.addEventListener('mouseleave', function(){
                                    clearTimeout(timer);
                                    // hide and clear content
                                    hoverBox.classList.remove('visible');
                                    hoverBox.innerHTML = '';
                                });
                        });

                        // Clean up when modal hidden
                        previewModalEl.addEventListener('hidden.bs.modal', function(){ 
                            // clear pdf state and canvases
                            pdfDoc = null; currentPage = 1;
                            ctxLeft.clearRect(0,0,canvasLeft.width||1, canvasLeft.height||1);
                            ctxRight.clearRect(0,0,canvasRight.width||1, canvasRight.height||1);
                            canvasRight.style.display = '';
                            document.getElementById('pdfPageIndicator').textContent = '0 / 0';
                        });

                        // Prev/Next handlers (toolbar)
                        document.getElementById('pdfPrev')?.addEventListener('click', function(){
                            if(!pdfDoc) return;
                            const newPage = Math.max(1, currentPage - 2);
                            animateTurn(newPage);
                        });
                        document.getElementById('pdfNext')?.addEventListener('click', function(){
                            if(!pdfDoc) return;
                            const newPage = Math.min(pdfDoc.numPages, currentPage + 2);
                            animateTurn(newPage);
                        });

                        // Side nav handlers
                        document.getElementById('pdfNavLeft')?.addEventListener('click', function(){
                            if(!pdfDoc) return;
                            const newPage = Math.max(1, currentPage - 2);
                            animateTurn(newPage);
                        });
                        document.getElementById('pdfNavRight')?.addEventListener('click', function(){
                            if(!pdfDoc) return;
                            const newPage = Math.min(pdfDoc.numPages, currentPage + 2);
                            animateTurn(newPage);
                        });

                        function setNavDisabled(disabled){
                            ['pdfPrev','pdfNext','pdfNavLeft','pdfNavRight'].forEach(id=>{ const el = document.getElementById(id); if(el) el.disabled = disabled; });
                        }
                })();
        </script>
</body>

</html>