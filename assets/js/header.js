// Dropdown-Funktionalität für Benutzermenü
const userDropdownToggle = document.getElementById('user-dropdown-toggle');
const userDropdownMenu = document.getElementById('user-dropdown-menu');

userDropdownToggle.addEventListener('click', function(event) {
    event.stopPropagation();
    userDropdownMenu.classList.toggle('active');
    // Benachrichtigungen-Dropdown schließen, wenn Benutzermenü geöffnet wird
    notificationDropdown.classList.remove('active');
});

// Dropdown-Funktionalität für Benachrichtigungen
const notificationToggle = document.getElementById('notification-toggle');
const notificationDropdown = document.getElementById('notification-dropdown');

notificationToggle.addEventListener('click', function(event) {
    event.stopPropagation();
    notificationDropdown.classList.toggle('active');
    // Benutzermenü schließen, wenn Benachrichtigungen geöffnet werden
    userDropdownMenu.classList.remove('active');
});

// Verhindern, dass Klicks im Dropdown-Menü das Menü schließen
document.querySelectorAll('.dropdown-menu, .notification-dropdown').forEach(dropdown => {
    dropdown.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});

// Schließen der Dropdowns, wenn außerhalb geklickt wird
document.addEventListener('click', function() {
    userDropdownMenu.classList.remove('active');
    notificationDropdown.classList.remove('active');
});

// Tastatur-Navigation verbessern
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        userDropdownMenu.classList.remove('active');
        notificationDropdown.classList.remove('active');
    }
});

// Theme-Umschaltung
const themeOptions = document.querySelectorAll('.theme-option');
const root = document.documentElement;

// Beim Laden die gespeicherte Einstellung anwenden
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || 'system';
    setTheme(savedTheme);

    // Aktive Schaltfläche markieren
    themeOptions.forEach(option => {
        if (option.getAttribute('data-theme') === savedTheme) {
            option.classList.add('active');
        }
    });
});

// Theme-Umschaltung
themeOptions.forEach(option => {
    option.addEventListener('click', (e) => {
        const selectedTheme = option.getAttribute('data-theme');

        // Aktiven Zustand aktualisieren
        themeOptions.forEach(btn => btn.classList.remove('active'));
        option.classList.add('active');

        // Theme anwenden
        setTheme(selectedTheme);

        // Einstellung speichern
        localStorage.setItem('theme', selectedTheme);

        // Verhindern, dass das Dropdown geschlossen wird
        e.stopPropagation();
    });
});

// Theme setzen basierend auf Auswahl
function setTheme(theme) {
    if (theme === 'system') {
        // System-Einstellung prüfen
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.setAttribute('data-theme', 'light');
        }

        // Auf Änderungen der System-Einstellung achten
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (localStorage.getItem('theme') === 'system') {
                root.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            }
        });
    } else {
        // Manuelles Theme setzen
        root.setAttribute('data-theme', theme);
    }
}

// Theme-Selector Funktionalität
document.addEventListener('DOMContentLoaded', function() {
    // Theme-Optionen
    const themeOptions = document.querySelectorAll('.theme-option');
    const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)');

    // Gespeichertes Theme aus localStorage abrufen
    const savedTheme = localStorage.getItem('theme');

    // Theme anwenden und aktive Klasse setzen
    function applyTheme(theme) {
        // Aktive Klasse von allen Theme-Optionen entfernen
        themeOptions.forEach(option => {
            option.classList.remove('active');
        });

        // Die entsprechende Option als aktiv markieren
        const activeOption = document.querySelector(`.theme-option[data-theme="${theme}"]`);
        if (activeOption) {
            activeOption.classList.add('active');
        }

        // Theme im Body-Element setzen
        if (theme === 'system') {
            // System-Einstellung verwenden
            if (prefersDarkMode.matches) {
                document.body.setAttribute('data-theme', 'dark');
            } else {
                document.body.setAttribute('data-theme', 'light');
            }
        } else {
            document.body.setAttribute('data-theme', theme);
        }

        // Theme im localStorage speichern
        localStorage.setItem('theme', theme);
    }

    // Initiales Theme anwenden
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        // Standardmäßig System-Theme verwenden
        applyTheme('system');
    }

    // Event-Listener für Theme-Änderungen
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            applyTheme(theme);
        });
    });

    // Auf Änderungen der System-Farbeinstellung reagieren
    prefersDarkMode.addEventListener('change', function() {
        if (localStorage.getItem('theme') === 'system') {
            applyTheme('system');
        }
    });
});