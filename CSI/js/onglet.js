function toggleMenu() {
    let sidebar = document.querySelector(".sidebar");
    sidebar.classList.toggle("show");

    if (sidebar.classList.contains("show")) {
        document.body.style.marginLeft = "220px";
    } else {
        document.body.style.marginLeft = "0";
    }
}