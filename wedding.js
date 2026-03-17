/* =========================
   CONFIGURATION OPTIONS
   ========================= */
const WeddingOptions = {
    showRSVP: true,
    showCountdown: true,
    darkMode: false,
    animateOnScroll: false,
    showMusic: false
};

/* =========================
   INITIALIZER
   ========================= */
document.addEventListener("DOMContentLoaded", () => {
    const venueElement = document.querySelector(".venue");

    if (WeddingOptions.showCountdown)   addCountdown("2026-09-27T18:00:00");
    if (WeddingOptions.showRSVP)        addRSVP();
    if (WeddingOptions.darkMode)        enableDarkMode();
    if (WeddingOptions.animateOnScroll) enableScrollAnimation();
    if (WeddingOptions.showMusic)       addBackgroundMusic();

    // Make venue a Google Maps link
    if (venueElement) {
        const venueName = venueElement.innerText;
        const mapUrl = "https://www.google.com/maps/search/?api=1&query=Venue+Name";
        venueElement.innerHTML = `
            <a href="${mapUrl}" target="_blank" class="location-link">
                <span class="pin-icon">📍</span> ${venueName}
            </a>`;
    }

    initGallery();
});

/* =========================
   COUNTDOWN TIMER
   ========================= */
function addCountdown(weddingDate) {
    const countdown = document.createElement("div");
    countdown.id = "countdown";
    const monogram = document.querySelector(".monogram-header");
    monogram ? monogram.after(countdown) : document.querySelector(".invitation").prepend(countdown);

    function render() {
        const diff = new Date(weddingDate) - new Date();
        if (diff <= 0) {
            countdown.innerHTML = `
                <div class="countdown-unit">
                    <span class="countdown-num" style="font-size:20px">🎉</span>
                    <span class="countdown-label">Today!</span>
                </div>`;
            return;
        }
        const d = Math.floor(diff / 864e5);
        const h = Math.floor((diff % 864e5) / 36e5);
        const m = Math.floor((diff % 36e5) / 6e4);
        const s = Math.floor((diff % 6e4) / 1e3);

        countdown.innerHTML = `
            <div class="countdown-unit">
                <span class="countdown-num">${d}</span>
                <span class="countdown-label">Days</span>
            </div>
            <span class="countdown-sep">·</span>
            <div class="countdown-unit">
                <span class="countdown-num">${String(h).padStart(2,'0')}</span>
                <span class="countdown-label">Hours</span>
            </div>
            <span class="countdown-sep">·</span>
            <div class="countdown-unit">
                <span class="countdown-num">${String(m).padStart(2,'0')}</span>
                <span class="countdown-label">Min</span>
            </div>
            <span class="countdown-sep">·</span>
            <div class="countdown-unit">
                <span class="countdown-num">${String(s).padStart(2,'0')}</span>
                <span class="countdown-label">Sec</span>
            </div>
        `;
    }

    render();
    setInterval(render, 1000);
}

/* =========================
   SCROLL FADE-IN
   ========================= */
function enableScrollAnimation() {
    const el = document.querySelector(".invitation");
    el.style.opacity = "0";
    window.addEventListener("scroll", () => {
        if (el.getBoundingClientRect().top < window.innerHeight - 100) {
            el.style.transition = "opacity 1s ease";
            el.style.opacity = "1";
        }
    });
}

/* =========================
   RSVP SECTION
   ========================= */
function addRSVP() {
    const ornament = document.createElement("div");
    ornament.className = "ornament";
    ornament.textContent = "✦";

    const rsvp = document.createElement("div");
    rsvp.className = "rsvp";
    rsvp.innerHTML = `
        <h2>RSVP</h2>
        <input type="text" id="rsvpName" placeholder="Your Full Name" maxlength="100" autocomplete="name">
        <button onclick="submitRSVP()">Confirm Attendance</button>
        <div id="rsvpMsg" role="alert"></div>
    `;

    const inv = document.querySelector(".invitation");
    inv.appendChild(ornament);
    inv.appendChild(rsvp);
}

function submitRSVP() {
    const nameInput = document.getElementById("rsvpName");
    const msg       = document.getElementById("rsvpMsg");
    const name      = nameInput.value.trim();

    // Clear previous state
    nameInput.classList.remove("error-shake");
    msg.innerHTML = "";

    // — Client-side validation —
    if (name.length === 0) {
        return showRSVPError(nameInput, msg, "Please enter your full name.");
    }
    if (name.length < 2) {
        return showRSVPError(nameInput, msg, "Name must be at least 2 characters.");
    }
    if (name.length > 100) {
        return showRSVPError(nameInput, msg, "Name is too long (max 100 characters).");
    }
    if (/[<>"';\\\/]/.test(name)) {
        return showRSVPError(nameInput, msg, "Name contains invalid characters.");
    }

    // — Submit —
    const btn = document.querySelector(".rsvp button");
    btn.textContent  = "Opening Menu…";
    btn.style.opacity = "0.6";
    btn.disabled = true;

    const form  = document.createElement("form");
    form.method = "POST";
    form.action = "rsvp.php";

    const input  = document.createElement("input");
    input.type   = "hidden";
    input.name   = "name";
    input.value  = name;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

function showRSVPError(input, msgEl, text) {
    input.classList.add("error-shake");
    msgEl.innerHTML   = text;
    msgEl.style.color = "#b03025";
    setTimeout(() => input.classList.remove("error-shake"), 400);
}

/* =========================
   PHOTO GALLERY LIGHTBOX
   ========================= */
function initGallery() {
    const items    = document.querySelectorAll(".gallery-item");
    const lightbox = document.getElementById("lightbox");
    if (!items.length || !lightbox) return;

    const lbImg   = document.getElementById("lightboxImg");
    const sources = [];
    let current   = 0;

    // Collect valid image sources; hide broken images gracefully
    items.forEach((item, i) => {
        const img = item.querySelector("img");
        if (img) {
            sources.push(img.src);
            img.addEventListener("error", () => {
                img.style.display = "none";
                item.classList.add("no-photo");
            });
        }
        item.addEventListener("click", () => openLightbox(i));
    });

    function openLightbox(index) {
        current   = index;
        lbImg.src = sources[current] || "";
        lightbox.classList.add("active");
        document.body.style.overflow = "hidden";
    }

    function closeLightbox() {
        lightbox.classList.remove("active");
        lbImg.src = "";
        document.body.style.overflow = "";
    }

    function shift(dir) {
        openLightbox((current + dir + sources.length) % sources.length);
    }

    document.getElementById("lightboxClose").addEventListener("click", closeLightbox);
    document.getElementById("lightboxPrev").addEventListener("click", () => shift(-1));
    document.getElementById("lightboxNext").addEventListener("click", () => shift(1));

    // Close on overlay click (not on image itself)
    lightbox.addEventListener("click", e => {
        if (e.target === lightbox) closeLightbox();
    });

    // Keyboard navigation
    document.addEventListener("keydown", e => {
        if (!lightbox.classList.contains("active")) return;
        if (e.key === "Escape")     closeLightbox();
        if (e.key === "ArrowLeft")  shift(-1);
        if (e.key === "ArrowRight") shift(1);
    });
}
