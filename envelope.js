const seal    = document.getElementById("seal");
const flap    = document.getElementById("flap");
const card    = document.getElementById("card");
const overlay = document.getElementById("envelopeOverlay");

let canOpen = false;

// Allow opening after entrance animation finishes
setTimeout(() => { canOpen = true; }, 2500);

seal.addEventListener("click", () => {
    if (!canOpen) return;

    // Open the flap and hide the seal
    flap.style.transform = "rotateX(-135deg)";
    seal.style.opacity   = "0";

    // Slide the inner card up briefly
    setTimeout(() => {
        card.style.top = "0";
    }, 600);

    // Fade out the overlay to reveal the invitation beneath
    setTimeout(() => {
        overlay.style.opacity       = "0";
        overlay.style.pointerEvents = "none";

        // Remove from layout once fully invisible
        setTimeout(() => {
            overlay.style.display = "none";
        }, 850);
    }, 900);
});
