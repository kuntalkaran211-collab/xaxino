function safePlaySound(filename) {
    playAudio(audioAssetPath, filename);
}


function createCardElement(frontImagePath, cardValue, isSelectable = false) {
    return $(`
        <div class="card-thumb ${isSelectable ? 'selectable' : ''} enter" data-card="${cardValue}">
            <div class="card-inner">
                <img class="card-face back" src="${cardBack}" alt="back">
                <img class="card-face front" src="${frontImagePath}" alt="${cardValue}">
            </div>
        </div>
    `);
}


function revealToContainer(container, cards, options = {}) {
    return new Promise(resolve => {
        const pathPrefix = cardPath;
        const stagger = options.stagger ?? 130;
        const flipDelay = options.flipDelay ?? 260;
        const selectable = options.selectable ?? false;

        let completed = 0;

        cards.forEach((card, idx) => {
            const frontPath = `${pathPrefix}/${card}.png`;
            const $elem = createCardElement(frontPath, card, selectable);
            container.append($elem);

            setTimeout(() => $elem.addClass('show'), 20 + idx * (stagger / 2));

            setTimeout(() => {
                safePlaySound('card-flip.mp3');
                $elem.addClass('flipped');
                setTimeout(() => $elem.removeClass('enter'), 400);
                completed++;
                if (completed === cards.length) resolve(); // resolve when all cards done
            }, flipDelay + idx * stagger);
        });

        if (cards.length === 0) resolve();
    });
}


async function revealPlayerCards(cardArray) {
    playerHighHand.empty();
    await revealToContainer(playerHighHand, cardArray, {
        selectable: true,
        stagger: 120,
        flipDelay: 200
    });
}


async function revealDealerCards(highCards, lowCards) {

    dealerHighHand.empty();
    await revealToContainer(dealerHighHand, highCards, {
        selectable: false,
        stagger: 180,
        flipDelay: 200
    });

    dealerLowHand.empty();

    await revealToContainer(dealerLowHand, lowCards, {
        selectable: false,
        stagger: 200,
        flipDelay: 150
    });
}


$(".resetBtn").on("click", function () {
  refresh();
});
function refresh() {
  window.location.reload();
}