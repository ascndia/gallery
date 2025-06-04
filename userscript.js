// ==UserScript==
// @name         Image URL Collector
// @namespace    http://tampermonkey.net/
// @version      1.4
// @description  Tambahkan tombol pada gambar untuk mengirim URL ke server dengan indikator sukses/gagal
// @author       Anda
// @match        *://*/*
// @exclude      http://localhost:8000/*
// @grant        GM_xmlhttpRequest
// @connect      localhost
// ==/UserScript==

(function () {
  "use strict";

  // Konfigurasi endpoint API
  const API_ENDPOINT = "http://localhost:8000/api/add.php";

  // Fungsi untuk membuat tombol
  function createButton() {
    const button = document.createElement("button");
    button.innerHTML = "📤";
    button.style.position = "absolute";
    button.style.top = "12px";
    button.style.right = "12px";
    button.style.zIndex = "9999";
    button.style.background = "rgba(0,0,0,0.6)";
    button.style.color = "white";
    button.style.border = "none";
    button.style.borderRadius = "3px";
    button.style.padding = "2px 5px";
    button.style.cursor = "pointer";
    button.style.fontSize = "16px";
    button.style.pointerEvents = "auto";
    return button;
  }

  // Fungsi untuk mengubah ikon sementara
  function changeIconTemporarily(button, icon) {
    const originalIcon = "📤";
    button.innerHTML = icon;
    setTimeout(() => {
      button.innerHTML = originalIcon;
    }, 1000);
  }

  // Fungsi untuk mengirim URL ke server
  function sendImageUrl(url, button) {
    GM_xmlhttpRequest({
      method: "POST",
      url: API_ENDPOINT,
      data: JSON.stringify({
        url: url,
        context: "",
        caption_short: "",
        caption_long: "",
        caption_tags: "",
      }),
      headers: {
        "Content-Type": "application/json",
      },
      onload: function (response) {
        if (response.status === 200) {
          changeIconTemporarily(button, "✅");
        } else {
          changeIconTemporarily(button, "❌");
          console.error("Error:", response.statusText);
        }
      },
      onerror: function (error) {
        changeIconTemporarily(button, "❌");
        console.error("Request gagal:", error.message);
      },
    });
  }

  // Fungsi utama untuk menambahkan tombol ke gambar
  function addButtonsToImages() {
    const images = document.querySelectorAll("img");
    images.forEach((img) => {
      // Skip gambar yang sudah ada tombolnya atau ukurannya terlalu kecil
      if (img.dataset.hasButton || img.width < 50 || img.height < 50) return;
      img.dataset.hasButton = "true";

      // Pastikan gambar memiliki posisi relatif untuk tombol absolut
      const parentStyle = window.getComputedStyle(img.parentElement);
      if (parentStyle.position === "static") {
        img.parentElement.style.position = "relative";
      }

      // Tambahkan tombol langsung sebagai sibling gambar
      const button = createButton();
      img.parentElement.appendChild(button);

      // Posisikan tombol relatif terhadap gambar
      button.style.left = `${
        img.offsetLeft + img.offsetWidth - button.offsetWidth - 5
      }px`;
      button.style.top = `${img.offsetTop + 5}px`;

      // Tambahkan event listener untuk tombol
      button.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        sendImageUrl(img.src, button);
      });
    });
  }

  // Jalankan saat halaman dimuat
  window.addEventListener("load", addButtonsToImages);

  // Observasi perubahan DOM untuk gambar yang dimuat secara dinamis
  const observer = new MutationObserver(addButtonsToImages);
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
})();
