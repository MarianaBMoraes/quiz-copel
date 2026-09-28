document.addEventListener("DOMContentLoaded", () => {
    const gameCodeInput = document.querySelector("[data-game-code]");

    if (!gameCodeInput) {
        return;
    }

    gameCodeInput.addEventListener("input", () => {
        let value = gameCodeInput.value.replace(/\D/g, "");

        value = value.slice(0, 6);

        if (value.length > 3) {
            value = `${value.slice(0, 3)} ${value.slice(3)}`;
        }

        gameCodeInput.value = value;
    });
});