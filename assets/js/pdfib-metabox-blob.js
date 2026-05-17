/**
 * PDFIB Metabox Blob Helpers
 *
 * Opens generated PDF and image blobs in a new browser tab with a viewer toolbar.
 * These functions build standalone HTML pages delivered via Blob URL -- they are
 * NOT injected into WordPress admin pages and cannot use wp_enqueue_style/script.
 *
 * @package PDF_Builder_Pro
 */

/* global URL, Blob */

/**
 * Opens a PDF blob in a new browser tab with a download/print toolbar.
 *
 * @param {Blob}   blob     The PDF Blob returned by the generation AJAX call.
 * @param {string} orderNum The WooCommerce order number for labelling.
 */
function openPdfBlob( blob, orderNum ) { // eslint-disable-line no-unused-vars
	var pdfUrl = URL.createObjectURL( blob );
	var css =
		'body{margin:0;padding:0;background:#525659;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}' +
		'.toolbar{position:fixed;top:0;left:0;right:0;height:48px;background:#323639;display:flex;align-items:center;gap:10px;padding:0 16px;z-index:1000;box-shadow:0 2px 8px rgba(0,0,0,.4);}' +
		'.btn{padding:8px 18px;border:none;border-radius:5px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:background .2s;}' +
		'.btn-dl{background:#2271b1;color:#fff;}.btn-dl:hover{background:#135e96;}' +
		'.btn-print{background:#10b981;color:#fff;}.btn-print:hover{background:#059669;}' +
		'.title{color:#ccc;font-size:13px;flex:1;}' +
		'iframe{position:fixed;top:48px;left:0;right:0;bottom:0;width:100%;height:calc(100% - 48px);border:none;}' +
		'@media print{.toolbar{display:none!important;}iframe{top:0;height:100%;}}';
	var inlineScript =
		'var ifr=document.getElementById("ifr");' +
		'function dl(){var a=document.createElement("a");a.href="' + pdfUrl + '";' +
		'a.download="commande-' + orderNum + '.pdf";a.click();}';
	var htmlPage =
		'<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">' +
		'<title>PDF Commande #' + orderNum + '</title>' +
		'<style>' + css + '</style>' +
		'</head><body>' +
		'<div class="toolbar">' +
		'<span class="title">&#x1F4C4; Commande #' + orderNum + '.pdf</span>' +
		'<button class="btn btn-dl" onclick="dl()">&#x1F4E5; T\u00e9l\u00e9charger</button>' +
		'<button class="btn btn-print" onclick="ifr.contentWindow.print()">&#x1F5A8; Imprimer</button>' +
		'</div>' +
		'<iframe id="ifr" src="' + pdfUrl + '" title="PDF Commande #' + orderNum + '"></iframe>' +
		'<script>' + inlineScript + '<\/script>' +
		'</body></html>';
	var pageBlob = new Blob( [ htmlPage ], { type: 'text/html;charset=utf-8' } );
	window.open( URL.createObjectURL( pageBlob ), '_blank' );
	setTimeout( function () { URL.revokeObjectURL( pdfUrl ); }, 120000 );
}

/**
 * Opens an image blob in a new browser tab with zoom/download/print toolbar.
 *
 * @param {Blob}   blob    The image Blob returned by the generation AJAX call.
 * @param {string} type    Image format: 'jpg' or 'png'.
 * @param {string} orderId The WooCommerce order ID for labelling.
 */
function openImageInTab( blob, type, orderId ) { // eslint-disable-line no-unused-vars
	var imageBlobUrl = URL.createObjectURL( blob );
	var mimeType     = ( type === 'jpg' ) ? 'image/jpeg' : 'image/png';
	var fileName     = 'facture-' + orderId + '.' + type;
	var css =
		'body{margin:0;padding:20px;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;flex-direction:column;align-items:center;}' +
		'.toolbar{position:fixed;top:20px;right:20px;display:flex;gap:12px;z-index:1000;}' +
		'.btn{padding:12px 24px;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,.15);}' +
		'.btn-download{background:#2271b1;color:white;}.btn-download:hover{background:#135e96;}' +
		'.btn-print{background:#10b981;color:white;}.btn-print:hover{background:#059669;}' +
		'.btn-zoom{background:#6b7280;color:white;padding:12px 16px;}.btn-zoom:hover{background:#4b5563;}' +
		'.zoom-level{background:#f3f4f6;color:#374151;padding:12px 16px;font-weight:bold;border-radius:6px;min-width:70px;text-align:center;}' +
		'.image-container{margin-top:60px;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 16px rgba(0,0,0,.1);max-width:50%;transform-origin:center top;}' +
		'img{max-width:100%;height:auto;display:block;}' +
		'@media print{body{background:white;padding:0;}.toolbar{display:none!important;}.image-container{margin:0;padding:0;box-shadow:none;}}';
	var inlineScript =
		'var dh="' + imageBlobUrl + '";' +
		'document.getElementById("facImg").addEventListener("load",function(){' +
		'try{var i=document.getElementById("facImg"),c=document.createElement("canvas");' +
		'c.width=i.naturalWidth;c.height=i.naturalHeight;c.getContext("2d").drawImage(i,0,0);' +
		'dh=c.toDataURL("' + mimeType + '");}catch(e){}});' +
		'var z=1,ct=document.getElementById("imageContainer"),zd=document.getElementById("zoomLevel");' +
		'function updateZoom(){ct.style.transform="scale("+z+")";zd.textContent=Math.round(z*100)+"%";}' +
		'function zoomIn(){if(z<3){z+=0.25;updateZoom();}}' +
		'function zoomOut(){if(z>0.25){z-=0.25;updateZoom();}}' +
		'function downloadImage(){var l=document.createElement("a");l.href=dh;l.download="' + fileName + '";document.body.appendChild(l);l.click();document.body.removeChild(l);}';
	var htmlPage =
		'<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">' +
		'<title>Facture ' + orderId + '</title>' +
		'<style>' + css + '</style>' +
		'</head><body>' +
		'<div class="toolbar">' +
		'<button class="btn btn-zoom" onclick="zoomOut()">-</button>' +
		'<div class="zoom-level" id="zoomLevel">100%</div>' +
		'<button class="btn btn-zoom" onclick="zoomIn()">+</button>' +
		'<button class="btn btn-download" onclick="downloadImage()">Telecharger</button>' +
		'<button class="btn btn-print" onclick="window.print()">Imprimer</button>' +
		'</div>' +
		'<div class="image-container" id="imageContainer">' +
		'<img id="facImg" src="' + imageBlobUrl + '" alt="Facture ' + orderId + '" crossorigin="anonymous" />' +
		'</div>' +
		'<script>' + inlineScript + '<\/script>' +
		'</body></html>';
	var pageBlob = new Blob( [ htmlPage ], { type: 'text/html; charset=utf-8' } );
	window.open( URL.createObjectURL( pageBlob ), '_blank' );
	setTimeout( function () { URL.revokeObjectURL( imageBlobUrl ); }, 60000 );
}