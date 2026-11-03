@extends('Template::layouts.master')
@section('content')
    <div class="pt-120 pb-120">
        <div class="container">
            <div class="row justify-content-center mt-2">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap justify-content-between gap-3 my-3">
                        <button type="button" class="cmn-btn triggerConfirmModal btn-sm">
                            @lang('New Transfer') <i class="fas fa-arrow-right-long"></i>
                        </button>

                        <form class=" flex-fill">
                            <div class="table--form ms-auto">
                                <div class="input-group">
                                    <input class="form-control" name="search" type="text"
                                        value="{{ request()->search }}" placeholder="@lang('TRX / Username...')">
                                    <button class="input-group-text bg-base text-white">
                                        <i class="las la-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table--responsive">
                                <table class="style--two table">
                                    <thead>
                                        <tr>
                                            <th>@lang('Receiver')</th>
                                            <th>@lang('Amount')</th>
                                            <th>@lang('Charge')</th>
                                            <th>@lang('Final Amount')</th>
                                            <th>@lang('Transaction Id')</th>
                                            <th>@lang('Date')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($balanceTransfers as $balanceTransfer)
                                            <tr>
                                                <td>{{ __($balanceTransfer->receiveUser->username) }}</td>
                                                <td>{{ showAmount($balanceTransfer->amount) }}</td>
                                                <td>{{ showAmount($balanceTransfer->charge) }}</td>
                                                <td>{{ showAmount($balanceTransfer->final_amount) }}</td>
                                                <td>{{ $balanceTransfer->trx }}</td>
                                                <td>{{ showDateTime($balanceTransfer->created_at) }}<br>{{ diffForHumans($balanceTransfer->created_at) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @if ($balanceTransfers->hasPages())
                            <div class="card-footer">
                                {{ paginateLinks($balanceTransfers) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmTransferModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content section--bg">
                <div class="modal-header">
                    <h6 class="modal-title">@lang('Transfer Balance')</h6>
                    <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <form action="{{ route('user.transfer.store') }}" method="POST"
                        class="no-submit-loader balance-transfer-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form--label">@lang('Username')</label>
                                    <input class="form--control form-control username" type="text" name="username"
                                        value="{{ old('username') }}" required>
                                    <div class="text-danger username-error"></div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form--label">@lang('Enter Amount')</label>
                                    <div class="input-group">
                                        <input class="form--control form-control input-amount" type="number" name="amount"
                                            value="{{ old('amount') }}" required>
                                        <span class="input-group-text">{{ gs('cur_text') }}</span>
                                    </div>
                                    <div class="text-danger amount-error"></div>
                                    <span class="fw-medium mt-2 d-flex justify-content-between flex-wrap gap-2">
                                        <span>
                                            @lang('Available Balance:')
                                            <span class="symbol">{{ gs('cur_sym') }}</span><span
                                                class="available-balance">{{ showAmount(auth()->user()->balance, currencyFormat: false) }}</span>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between gap-2 px-0">
                                            <span>@lang('Amount')</span>
                                            <span>
                                                <span class="amount">@lang('0.00')</span>
                                                <span>{{ __(gs('cur_text')) }}</span>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between gap-2 px-0">
                                            <span>@lang('Charge')</span>
                                            <span>
                                                <span class="charge">@lang('0.00')</span>
                                                <span>{{ __(gs('cur_text')) }}</span>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between gap-2 px-0">
                                            <span>@lang('Final Amount')</span>
                                            <span>
                                                <span class="final-amount">@lang('0.00')</span>
                                                <span>{{ __(gs('cur_text')) }}</span>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="button" class="cmn-btn w-100 mt-3 confirmTransferBtn">
                                    @lang('Transfer') <i class="fas fa-arrow-right-long"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";

            //calculation the final amount on input
            const $inputAmountElement = $('.amount');
            const $chargeElement = $('.charge');
            const $finalAmountElement = $('.final-amount');
            const sendMonyFixedCharge = parseFloat("{{ gs('balance_transfer_fixed_charge') }}");
            const sendMonyPercentCharge = parseFloat("{{ gs('balance_transfer_percent_charge') }}");

            const userBalance = parseFloat("{{ $user->balance }}");

            $(".input-amount").on('input', function() {
                let amount = parseFloat($(this).val() || 0);

                if (amount === 0) {
                    $('.amount-error').text('');
                    $inputAmountElement.text('0');
                    $chargeElement.text('0');
                    $finalAmountElement.text('0');
                    return;
                }

                let charge = parseFloat((amount / 100 * sendMonyPercentCharge) + sendMonyFixedCharge);
                let finalAmount = parseFloat(amount + charge);

                if (finalAmount > userBalance) {
                    $('.amount-error').text("@lang('The entered amount exceeds your available balance.')");
                    $inputAmountElement.text('0');
                    $chargeElement.text('0');
                    $finalAmountElement.text('0');
                    $(".balance-transfer-form").find(`button[type=submit]`).attr('disabled', true);
                    return;
                } else {
                    $(".balance-transfer-form").find(`button[type=submit]`).attr('disabled', false);
                    $('.amount-error').text('');
                }

                $inputAmountElement.text(amount.toFixed(2));
                $chargeElement.text(charge.toFixed(2));
                $finalAmountElement.text(finalAmount.toFixed(2));
            });

            $('.username').on('focusout', function() {
                var username = $(this).val();
                if (!username.length) return;

                $.ajax({
                    url: '{{ route('user.transfer.validate.username') }}',
                    method: 'GET',
                    data: {
                        username: username
                    },
                    success: function(response) {
                        if (response.exists) {
                            $('.username-error').text('');
                            $(".balance-transfer-form").find(`button[type=submit]`).attr('disabled',
                                false);
                        } else {
                            $('.username-error').text(response.error);
                            $(".balance-transfer-form").find(`button[type=submit]`).attr('disabled',
                                true);
                        }
                    }
                });
            });

            let form = $('.balance-transfer-form');

            $('.triggerConfirmModal').on('click', function() {
                $('#confirmTransferModal').modal('show');
            });
            $('.confirmTransferBtn').on('click', function() {
                $('#confirmTransferModal').modal('hide');
                form.submit();
            });

        })(jQuery);
    </script>
@endpush
