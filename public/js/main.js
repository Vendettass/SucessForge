// ------------------------------
// CONFIG
// ------------------------------

const API_BASE = "SuccesForge/api";   // Modifier si besoin
const dom = {
    inputSteamId: document.getElementById("steamid-input"),
    loadBtn: document.getElementById("load-btn"),
    message: document.getElementById("message"),
    gamesGrid: document.getElementById("games-grid"),
    achPanel: document.getElementById("achievements-panel"),
    achTitle: document.getElementById("achievements-title"),
    achList: document.getElementById("achievements-list"),
};


// ------------------------------
// EVENTS
// ------------------------------

dom.loadBtn.addEventListener("click", async () => {
    const value = dom.inputSteamId.value.trim();
    if (!value) {
        dom.message.textContent = "Merci d'entrer un SteamID64 ou un pseudo.";
        return;
    }

    // Si c’est un pseudo, on le convertit en SteamID
    let steamid = value;

    if (isNaN(value)) { // si ce n'est pas un nombre → vanity URL
        steamid = await resolveVanityURL(value);
        if (!steamid) {
            dom.message.textContent = "Impossible de trouver ce pseudo Steam.";
            return;
        }
    }

    loadGames(steamid);
});


// ------------------------------
// CALL API FUNCTIONS
// ------------------------------

async function resolveVanityURL(vanityName) {
    try {
        const res = await fetch(`${API_BASE}/resolveVanity.php?vanity=${encodeURIComponent(vanityName)}`);
        if (!res.ok) throw new Error("Erreur HTTP " + res.status);

        const data = await res.json();
        return data.steamid || null;

    } catch (err) {
        console.error("Erreur resolveVanityURL:", err);
        return null;
    }
}

async function loadGames(steamid) {
    dom.message.textContent = "Chargement des jeux...";
    dom.gamesGrid.innerHTML = "";
    dom.achPanel.classList.add("hidden");

    try {
        const res = await fetch(`${API_BASE}/games.php?steamid=${encodeURIComponent(steamid)}`);
        if (!res.ok) throw new Error("Erreur HTTP " + res.status);

        const games = await res.json();

        if (!Array.isArray(games) || games.length === 0) {
            dom.message.textContent = "Aucun jeu trouvé (SteamID incorrect ou bibliothèque privée).";
            return;
        }

        dom.message.textContent = `${games.length} jeu(x) trouvé(s). Clique sur un jeu pour voir les succès.`;

        renderGames(games);

    } catch (err) {
        console.error("Erreur loadGames:", err);
        dom.message.textContent = "Erreur lors de la récupération des jeux.";
    }
}

async function loadAchievements(appid, gameName) {
    dom.message.textContent = `Chargement des succès pour ${gameName}...`;
    dom.achPanel.classList.add("hidden");
    dom.achList.innerHTML = "";

    try {
        const res = await fetch(`${API_BASE}/achievements.php?appid=${encodeURIComponent(appid)}`);
        if (!res.ok) throw new Error("Erreur HTTP " + res.status);

        const achievements = await res.json();

        renderAchievements(gameName, achievements);
        dom.message.textContent = "";

    } catch (err) {
        console.error("Erreur loadAchievements:", err);
        dom.message.textContent = "Erreur lors de la récupération des succès.";
    }
}


// ------------------------------
// RENDER FUNCTIONS
// ------------------------------

function renderGames(games) {
    dom.gamesGrid.innerHTML = "";

    games.forEach(game => {
        const card = document.createElement("button");
        card.className =
            "bg-slate-800 rounded-2xl overflow-hidden shadow hover:shadow-lg " +
            "hover:-translate-y-1 transition flex flex-col text-left";

        card.innerHTML = `
            <img src="${game.cover}" alt="${game.name}" class="w-full object-cover">
            <div class="p-3">
                <h3 class="font-semibold text-sm">${game.name}</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Temps de jeu : ${(game.playtime_forever / 60).toFixed(1)} h
                </p>
            </div>
        `;

        card.addEventListener("click", () => {
            loadAchievements(game.appid, game.name);
        });

        dom.gamesGrid.appendChild(card);
    });
}

function renderAchievements(gameName, achievements) {
    dom.achTitle.textContent = `Succès pour ${gameName}`;
    dom.achList.innerHTML = "";

    if (!achievements.length) {
        dom.achList.innerHTML = `
            <p class="text-slate-400 text-sm">
                Aucun succès détecté pour ce jeu.
            </p>`;
    } else {
        achievements.forEach(ach => {
            const div = document.createElement("div");
            div.className = "flex gap-3 bg-slate-800 rounded-xl p-3";

            div.innerHTML = `
                <img src="${ach.icon || ach.icon_gray}"
                     alt="${ach.name}"
                     class="w-10 h-10 rounded-md object-cover flex-shrink-0">

                <div>
                    <p class="font-semibold text-sm">${ach.name}</p>
                    <p class="text-xs text-slate-300">${ach.description || ""}</p>
                    <p class="text-xs text-slate-400 mt-1">
                        Débloqué par ${
                            ach.percent_global !== null
                                ? ach.percent_global.toFixed(2)
                                : "N/A"
                        }% des joueurs
                    </p>
                </div>
            `;

            dom.achList.appendChild(div);
        });
    }

    dom.achPanel.classList.remove("hidden");
}