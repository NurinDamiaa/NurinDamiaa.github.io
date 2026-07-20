(function () {
    function buildModal() {
        var box = document.createElement('div');
        box.className = 'lightbox';
        box.innerHTML =
            '<button class="lightbox-close" aria-label="Close">&times;</button>' +
            '<img class="lightbox-img" alt="">' +
            '<div class="lightbox-caption"></div>';
        document.body.appendChild(box);
        return box;
    }

    function getCaption(img) {
        var fig = img.closest('figure');
        if (fig) {
            var cap = fig.querySelector('figcaption');
            if (cap && cap.textContent.trim()) return cap.textContent.trim();
        }
        return img.getAttribute('alt') || '';
    }

    function init() {
        var modal = buildModal();
        var imgEl = modal.querySelector('.lightbox-img');
        var capEl = modal.querySelector('.lightbox-caption');
        var closeBtn = modal.querySelector('.lightbox-close');

        function openModal(src, caption) {
            imgEl.src = src;
            capEl.textContent = caption || '';
            capEl.style.display = caption ? 'block' : 'none';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('open');
            document.body.style.overflow = '';
            imgEl.src = '';
        }

        var images = document.querySelectorAll('.content img');
        images.forEach(function (img) {
            if (img.closest('a')) return;
            if (img.closest('.hero-header')) return;
            img.classList.add('zoomable');
            img.addEventListener('click', function () {
                openModal(img.currentSrc || img.src, getCaption(img));
            });
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target === imgEl || e.target === closeBtn) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    }

    function initAutoplayUnlock() {
        var audios = document.querySelectorAll('audio[autoplay]');
        if (!audios.length) return;

        function tryPlay() {
            audios.forEach(function (a) {
                if (a.paused) {
                    var p = a.play();
                    if (p && typeof p.catch === 'function') p.catch(function () {});
                }
            });
        }

        tryPlay();

        var events = ['click', 'touchstart', 'keydown', 'scroll'];
        function unlock() {
            tryPlay();
            events.forEach(function (ev) {
                window.removeEventListener(ev, unlock, true);
            });
        }
        events.forEach(function (ev) {
            window.addEventListener(ev, unlock, true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
            initAutoplayUnlock();
        });
    } else {
        init();
        initAutoplayUnlock();
    }
})();
