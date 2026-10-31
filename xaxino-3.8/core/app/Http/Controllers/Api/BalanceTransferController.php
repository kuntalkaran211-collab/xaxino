<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BalanceTransfer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BalanceTransferController extends Controller
{
    public function checkUser(Request $request)
    {
        $validator = [
            'username' => 'required',
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            $notify[] = 'User not found';
            return responseError('user_not_found', $notify);
        }

        $notify[] = 'User fetched successfully';
        return responseSuccess('success', $notify, [
            'status' => $user->status,
            'user' => $user
        ]);
    }

    public function transferBalance(Request $request)
    {
        if (!gs('balance_transfer')) {
            $notify[] = 'Balance transfer is disabled';
            return responseError('disabled', $notify);
        }

        $validator = [
            'username' => 'required',
            'amount'   => 'required|numeric|gt:0',
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $sender   = auth()->user();
        $receiver = User::where('username', $request->username)->where('status', Status::USER_ACTIVE)->first();

        if (!$receiver) {
            $notify[] = 'Receiver not found';
            return responseError('receiver_not_found', $notify);
        }

        if ($sender->id  == $receiver->id) {
            $notify[] = 'You can\'t transfer balance to your own account';
            return responseError('own_account', $notify);
        }

        $fixedCharge   = gs('balance_transfer_fixed_charge');
        $percentCharge = gs('balance_transfer_percent_charge');
        $charge        = $fixedCharge + ($percentCharge * $request->amount) / 100;

        $finalAmount = $request->amount + $charge;

        if ($sender->balance < $finalAmount) {
            $notify[] = 'Insufficient balance to complete the transaction';
            return responseError('insufficient_balance', $notify);
        }

        $balanceTransfer               = new BalanceTransfer();
        $balanceTransfer->sender_id    = $sender->id;
        $balanceTransfer->receiver_id  = $receiver->id;
        $balanceTransfer->amount       = $request->amount;
        $balanceTransfer->charge       = $charge;
        $balanceTransfer->final_amount = $finalAmount;
        $balanceTransfer->trx          = getTrx();
        $balanceTransfer->save();

        $sender->balance -= $finalAmount;
        $sender->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $sender->id;
        $transaction->amount       = $finalAmount;
        $transaction->charge       = $charge;
        $transaction->post_balance = $sender->balance;
        $transaction->trx_type     = '-';
        $transaction->trx          = $balanceTransfer->trx;
        $transaction->details      = "Balance Transfer " . showAmount($request->amount) . " to $receiver->username.";
        $transaction->remark       = "balance_transfer";
        $transaction->save();

        $receiver->balance += $request->amount;
        $receiver->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $receiver->id;
        $transaction->amount       = $request->amount;
        $transaction->charge       = 0;
        $transaction->post_balance = $receiver->balance;
        $transaction->trx_type     = '+';
        $transaction->trx          = $balanceTransfer->trx;
        $transaction->details      = "Balance Transfer received " . showAmount($request->amount) . " from $sender->username.";
        $transaction->remark       = "receive_transfer";
        $transaction->save();

        notify($receiver, 'BALANCE_TRANSFER', [
            'sender'       => $sender->username,
            'sent_at'      => showDateTime($balanceTransfer->created_at),
            'amount'       => showAmount($request->amount, currencyFormat: false),
            'trx'          => $balanceTransfer->trx,
            'post_balance' => showAmount($receiver->balance, currencyFormat: false),
        ]);

        $notify[] = 'Balance transfer successful';
        return responseSuccess('success', $notify, [
            'balanceTransfer' => $balanceTransfer,
            'amount'          => $balanceTransfer->amount,
            'charge'          => $charge,
            'fixed_charge'    => $fixedCharge,
            'percent_charge'  => $percentCharge
        ]);
    }

    public function transferHistory()
    {
        $transfers = BalanceTransfer::with('receiveUser:id,username')->where('sender_id', auth()->user()->id)->searchable(['trx', "receiveUser:username"])->latest()->paginate(getPaginate());
        return responseSuccess('success', 'Transfer history', $transfers);
    }
}
