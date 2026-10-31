<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BalanceTransfer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class BalanceTransferController extends Controller {
    public function index() {
        $pageTitle        = "Balance Transfer";
        $user             = auth()->user();
        $balanceTransfers = BalanceTransfer::where('sender_id', $user->id)->with('receiveUser')->searchable(['trx', "receiveUser:username"])->latest()->paginate(getPaginate());
        return view('Template::user.transfer.index', compact('pageTitle', 'balanceTransfers', 'user'));
    }

    public function store(Request $request) {
        $request->validate([
            'username' => 'required',
            'amount'   => 'required|numeric|gt:0',
        ]);

        if (!gs('balance_transfer')) {
            $notify[] = ['error', 'Balance transfer is not available!'];
            return back()->withNotify($notify);
        }

        $sender   = auth()->user();
        $receiver = User::where('username', $request->username)->where('status', Status::USER_ACTIVE)->first();

        if (!$receiver) {
            $notify[] = ['error', 'Receiver not found!'];
            return back()->withNotify($notify)->withInput();
        }

        if ($sender->id == $receiver->id) {
            $notify[] = ['error', 'You can\'t transfer balance to your own account!'];
            return back()->withNotify($notify)->withInput();
        }

        $fixedCharge   = gs('balance_transfer_fixed_charge');
        $percentCharge = gs('balance_transfer_percent_charge');
        $charge        = $fixedCharge + ($percentCharge * $request->amount) / 100;

        $finalAmount = $request->amount + $charge;

        if ($sender->balance < $finalAmount) {
            $notify[] = ['error', 'Insufficient balance to complete the transaction'];
            return back()->withNotify($notify)->withInput();
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
        $transaction->remark       = "balance_received";
        $transaction->save();

        notify($receiver, 'BALANCE_TRANSFER', [
            'sender'       => $sender->username,
            'sent_at'      => showDateTime($balanceTransfer->created_at),
            'amount'       => showAmount($request->amount, currencyFormat: false),
            'trx'          => $balanceTransfer->trx,
            'post_balance' => showAmount($receiver->balance, currencyFormat: false),
        ]);

        $notify[] = ['success', 'Balance transfer successful!'];
        return redirect()->route('user.transfer.index')->withNotify($notify);
    }

    public function validateUsername(Request $request) {
        $request->validate([
            'username' => 'required',
        ]);

        $user = auth()->user();

        $receivedUser = User::where('username', $request->username)->first();

        if (!$receivedUser) {
            return response()->json(['exists' => false, 'error' => 'Receiver not found']);
        }
        if ($user->id == $receivedUser->id) {
            return response()->json(['exists' => false, 'error' => 'Cannot transfer to yourself']);
        }
        return response()->json(['exists' => true]);
    }
}
