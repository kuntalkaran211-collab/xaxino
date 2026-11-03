@extends('Template::layouts.master')
@section('content')
    <section class="pt-120 pb-120">
        <div class="container">
            <div class="row gy-4">
                <!-- Game Table Section -->
                <div class="col-xl-9" id="game--card">
                    <div class="game--card">
                        <div class="pai-gow-table">
                            <!-- Dealer Section -->
                            <div class="dealer-section mb-4">
                                <h5 class="dealer-section__text">@lang('Dealer Hand')</h5>
                                <div class="dealer-section__top">
                                    <h5 class="dealer-section__title">@lang('HIGHEST')</h5>
                                    <div class="d-md-block d-none">
                                        <span class="dealer-section__title">@lang('2ND HIGHEST')</span>
                                    </div>
                                </div>
                                <div class="dealer-section__inner">
                                    <div class="hand high-hand">
                                        <div class="cards d-flex" id="dealer-high-hand">
                                            @for ($i = 0; $i < 5; $i++)
                                                <div class="card-thumb">
                                                    <div class="card-inner">
                                                        <img class="card-face back"
                                                             src="{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
                                                             alt="card back">
                                                        <img class="card-face front"
                                                             src="{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
                                                             alt="card front">
                                                    </div>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="hand low-hand ">
                                        <div class="d-md-none d-block">
                                            <span class="dealer-section__title mb-2">@lang('2ND HIGHEST')</span>
                                        </div>
                                        <div class="cards d-flex" id="dealer-low-hand">
                                            @for ($i = 0; $i < 2; $i++)
                                                <div class="card-thumb">
                                                    <div class="card-inner">
                                                        <img class="card-face back"
                                                             src="{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
                                                             alt="card back">
                                                        <img class="card-face front"
                                                             src="{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
                                                             alt="card front">
                                                    </div>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Player Section -->
                            <div class="player-section">
                                <h5 class="player-section__title">@lang('Your Hand')</h5>
                                <div class="hand low-hand">
                                    <div class="cards d-flex justify-content-between" id="player-low-hand">
                                        <div class="low-slot">
                                            <span class="low-slot__text">@lang('2ND HIGHEST')</span>
                                            <div class="player-low-hand-shape"></div>
                                        </div>
                                        <div class="low-slot">
                                            <span class="low-slot__text">@lang('2ND HIGHEST')</span>
                                            <div class="player-low-hand-shape"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="hand high-hand mb-2">
                                    <span class="high-hand__title">@lang('HIGHEST')</span>
                                    <div class="cards d-flex justify-content-center" id="player-high-hand">
                                        @for ($i = 0; $i < 7; $i++)
                                            <div class="card-thumb" data-card-index="{{ $i }}">
                                                <div class="card-inner">
                                                    <img class="card-face back"
                                                         src="{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
                                                         alt="card back">
                                                    <img class="card-face front"
                                                         src="{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
                                                         alt="card front">
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                <button type="button" class="cmn-btn standBtn d-none">@lang('Stand')</button>
                                <button type="button" class="cmn-btn resetBtn d-none">@lang('Refresh')</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Betting & Info Section -->
                <div class="col-xl-3" id="form--area">
                    <div class="card custom--card">
                        <div class="card-body">
                            <h3 class="f-size--28 mb-4 text-center">
                                {{ $isDemo ? trans('Demo Balance:') : trans('Current Balance:') }}
                                <span class="base--color">
                                    <span
                                          class="balance text-white">{{ showAmount($balance, currencyFormat: false) }}</span>
                                    {{ __(gs('cur_text')) }}
                                </span>
                            </h3>
                            <form id="game" method="post">
                                @csrf
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <input class="form-control" name="invest" type="number" step="any"
                                               value="{{ old('invest') }}" placeholder="@lang('Enter amount')"
                                               autocomplete="off">
                                        <span class="input-group-text">{{ __(gs('cur_text')) }}</span>
                                    </div>
                                    <small class="form-text text-muted"><i class="fas fa-info-circle mr-2"></i>
                                        @lang('Invest Limit')
                                        : {{ showAmount($game->min_limit) }} - {{ showAmount($game->max_limit) }}
                                    </small>
                                </div>
                                <button class="cmn-btn w-100 investBtn" id="flip"
                                        type="submit">@lang('Play Now')</button>
                            </form>
                            <div class="text-center">
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <a class="game-instruction" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">
                                        @lang('Game Instruction')
                                        <i class="las la-info-circle"></i>
                                    </a>
                                    <button type="button" class="cmn-btn btn-sm sound-btn bg--two audioBtn">
                                        <i class="fas fa-volume-up"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Game Instruction Modal -->
    <div class="modal custom--modal fade" id="exampleModalCenter">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content section--bg">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Game Rule')</h5>
                    <span class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    @php echo $game->instruction @endphp
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/global/js/soundControl.js') }}"></script>
    <script src="{{ asset('assets/global/js/game/pai-gow-poker.js') }}"></script>
@endpush

@push('style')
    <style>
        /* --- Layout --- */
        .pai-gow-table .hand {
            margin-bottom: 15px;
        }

        .pai-gow-table .cards {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* card container */
        .card-thumb {
            width: 80px;
            height: 120px;
            perspective: 900px;
            cursor: pointer;
            position: relative;
        }

        .card-inner {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s ease;
            border-radius: 6px;
        }

        .card-face {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 6px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2);
            display: block;
            object-fit: cover;
        }

        .card-face.back {
            transform: rotateY(0deg);
        }

        .card-face.front {
            transform: rotateY(180deg);
        }

        /* flipped state (show front) */
        .card-thumb.flipped .card-inner {
            transform: rotateY(180deg);
        }

        /* entry animation (slide + fade) */
        .card-thumb.enter {
            transform: translateY(10px);
            opacity: 0;
        }

        .card-thumb.enter.show {
            transform: translateY(0);
            opacity: 1;
            transition: transform 0.35s ease, opacity 0.35s ease;
        }

        /* selectable hover */
        .card-thumb.selectable:hover {
            transform: translateY(-6px) scale(1.06);
            transition: transform 0.18s ease;
            z-index: 5;
        }

        /* selected to low-hand bounce */
        .card-thumb.moving {
            transition: transform 0.45s cubic-bezier(.2, .9, .2, 1), opacity 0.45s ease;
        }

        /* keep original card-item styles for bonus table */
        .card-item::before,
        .card-item::after {
            background-image: unset;
        }

        .card-item {
            border: none;
        }

        .btn--gradient.callBtn,
        .btn--gradient.foldBtn,
        .btn--gradient.dealBtn,
        .btn--gradient.refreshBtn {
            padding: 12px 20px !important;
            font-size: 20px !important;
            border-radius: 8px;
        }

        /* make sure front image looks correct when rotated */
        .card-face.front {
            transform: rotateY(180deg);
        }

        .player-section {
            background: #01162f;
            padding: 32px 48px;
            border-radius: 8px;
        }

        .player-section__title,
        .dealer-section__text {
            text-align: center;
            margin-bottom: 20px;
        }

        .high-hand__title {
            display: flex;
            justify-content: center;
            margin-bottom: 5px;
            color: #fff;
            letter-spacing: 2px;
            font-size: 20px;
            font-weight: 400;
        }

        .player-section .cmn-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 250px;
            margin: 0 auto;
            margin-top: 20px;
        }

        .dealer-section {
            background: #01162f;
            padding: 32px 48px;
            border-radius: 8px;
        }

        .dealer-section__inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }


        .dealer-section__top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }



        .dealer-section__title {
            display: flex;
            justify-content: center;
            color: #fff;
            letter-spacing: 2px;
            font-size: 20px;
            font-weight: 400;
            margin-bottom: 0;
            line-height: 1;
            font-family: var(--body-font);
        }

        .player-low-hand-shape {
            width: 80px;
            height: 120px;
            /* background: #fff; */
            border-radius: 6px;
            border: 1px dashed rgba(255, 255, 255, 0.5);
        }

        .low-slot {
            width: 80px;
            height: 120px;
            position: relative;
        }

        .low-slot:first-child .low-slot__text {
            position: absolute;
            left: -30px;
            top: 0;
            transform: rotate(-90deg);
            color: #fff;
            letter-spacing: 2px;
            font-size: 14px;
            font-weight: 400;
            width: 120px;
            height: 100%;
            white-space: nowrap;
        }

        .low-slot:last-child .low-slot__text {
            position: absolute;
            right: -30px;
            top: 0;
            transform: rotate(-90deg);
            color: #fff;
            letter-spacing: 2px;
            font-size: 14px;
            font-weight: 400;
            width: 120px;
            white-space: nowrap;
            height: 100%;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        .low-slot .card-thumb {
            position: absolute;
            inset: 0;
        }

        .player-section .hand.low-hand {
            padding: 0 32px;
        }

        @media (max-width:767px) {
            .dealer-section__inner {
                flex-direction: column;
            }

            .dealer-section__top {
                justify-content: center
            }

            .pai-gow-table .cards {
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width:575px) {

            .dealer-section,
            .player-section {
                padding: 20px;
            }

            .player-section .hand.low-hand {
                padding: 0 32px;
            }

            .card-thumb {
                width: 70px;
                height: 100px;
            }

            .low-slot {
                width: 70px;
                height: 100px;
            }

            .player-low-hand-shape {
                width: 70px;
                height: 100px;
            }

            .low-slot:first-child .low-slot__text,
            .low-slot:last-child .low-slot__text {
                width: 100px;
                font-size: 12px;
            }
        }
    </style>
@endpush


@push('script')
    <script>
        let userBalance = Number("{{ $balance }}");
        let minLimit = Number("{{ $game->min_limit }}");
        let maxLimit = Number("{{ $game->max_limit }}");
        let currency = "{{ gs('cur_text') }}";
        let investField = $("[name=invest]");
        let investBtn = $(".investBtn");
        let balance = $(".balance");
        let dealerHighHand = $('#dealer-high-hand');
        let dealerLowHand = $('#dealer-low-hand');
        let playerHighHand = $('#player-high-hand');
        let playerLowHand = $('#player-low-hand');
        audioAssetPath = `{{ asset('assets/audio') }}`;
        let gameLogId = null;
        const cardBack = "{{ asset(activeTemplate(true) . 'images/cards/BACK.png') }}"
        const cardPath = "{{ asset(activeTemplate(true) . 'images/cards') }}";


        $(document).ready(function() {
            $('#player-high-hand').on('click', '.selectable', function() {
                safePlaySound("click.mp3");

                const $emptySlot = $('#player-low-hand .low-slot')
                    .filter(function() {
                        return $(this).find('.card-thumb').length === 0;
                    })
                    .first();

                if (!$emptySlot.length) {
                    return notify('error', 'You can select maximum 2 cards');
                }

                const $card = $(this);
                const $clone = $card.clone()
                    .removeClass('selectable')
                    .addClass('moving');

                $emptySlot.append($clone);
                $card.remove();

                setTimeout(() => $clone.removeClass('moving'), 400);
            });
        });


        $('#game').on('submit', async function(e) {
            e.preventDefault();
            safePlaySound("click.mp3");

            const invest = investField.val();

            if (!invest) return notify('error', 'Invest field is required');
            if (invest < minLimit) return notify('error', `Minimum invest is ${minLimit}`);
            if (invest > maxLimit) return notify('error', `Maximum invest is ${maxLimit}`);
            if (invest > userBalance) return notify('error', 'Insufficient balance');

            investBtn.addClass("d-none");
            investField.prop('readonly', true);


            const url = "{{ route('user.play.invest', [$game->alias, @$isDemo]) }}";

            let response = await $.post(url, {
                _token: "{{ csrf_token() }}",
                invest: invest
            });

            if (response.error) {
                investBtn.removeClass("d-none");
                investField.prop('readonly', false);
                return notify("error", response.error);
            }

            investField.val("");

            $('.standBtn').removeClass("d-none");

            await revealPlayerCards(response.result);

            gameLogId = response.game_log_id;
            balance.text(response.balance);
            notify("success", response.message);
        });


        $('.standBtn').on('click', async function() {

            let lowHandCards = [];
            $('#player-low-hand .card-thumb').each(function() {
                lowHandCards.push($(this).data('card'));
            });

            if (lowHandCards.length < 2) {
                return notify('error', 'Select 2 cards for Low Hand');
            }

            let highHandCards = [];
            $('#player-high-hand .card-thumb').each(function() {
                highHandCards.push($(this).data('card'));
            });


            const url = "{{ route('user.play.end', [$game->alias, @$isDemo]) }}";

            let response = await $.post(url, {
                _token: "{{ csrf_token() }}",
                game_log_id: gameLogId,
                high_hand: highHandCards,
                low_hand: lowHandCards
            });

            await revealDealerCards(response.dealerCards.high, response.dealerCards.low);
            balance.text(response.bal);

            $('.standBtn').addClass("d-none");
            $('.resetBtn').removeClass("d-none");
            $(".win-loss-popup").addClass("active");

            $(".win-loss-popup").addClass("active");
            $(".win-loss-popup__body").find("img").addClass("d-none");

            if (response.type == "success") {
                $(".win-loss-popup__body").find(".win").removeClass("d-none");
                $('.data-result').text('Player Win');
            } else if (response.type == "push") {
                $('.data-result').text('Draw');
                $(".win-loss-popup__body").find(".push").removeClass("d-none");

            } else {
                $('.data-result').text('Dealer Win');
                $(".win-loss-popup__body").find(".lose").removeClass("d-none");
            }

        });
    </script>
@endpush
