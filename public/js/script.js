[...document.getElementsByClassName("backlink-js")].forEach((b) => {
    b.addEventListener("click", () => window.history.back());
});
