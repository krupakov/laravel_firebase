<?php

namespace App\Http\Controllers\Auth;

use Kreait\Firebase\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Lcobucci\JWT\Parser;

use App\Models\Phone;
use App\Models\User;

class FirebaseAuthController extends Controller
{
	use AuthenticatesUsers;

	/** @var Auth */
    private $auth;

    public function __construct(Auth $firebase)
    {
        $this->auth = $firebase;
    }

	/**
	 * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
	public function register(Request $request)
	{
		$request->validate([
			'token' => 'required',
			'name' => 'required|max:255',
			'password' => 'required|min:6|confirmed',
		]);

		try {
			$verifiedIdToken = $this->auth->verifyIdToken((string) $request->token);

			$parsedIdToken = (new Parser())->parse((string) $verifiedIdToken);
			
			$uid = $parsedIdToken->getClaim('sub');
			$firebaseUser = $this->auth->getUser($uid);
			$phoneNumber = $firebaseUser->phoneNumber;
		} catch (InvalidToken $e) {
			return response()->json([
				'status' => trans('firebase.token_invalid', ['error' => $e->getMessage()]),
			], 400);
		} catch (\InvalidArgumentException $e) {
			return response()->json([
				'status' => trans('firebase.token_parse', ['error' => $e->getMessage()]),
			], 400);
		} catch (UserNotFound $e) {
			return response()->json([
				'status' => trans('firebase.firebase_user_not_found'),
			], 400);
		}

		if (Phone::where('number', $phoneNumber)->exists()) {
			return response()->json([
				'status' => trans('firebase.phone_exists'),
			], 400);
		}

		$user = User::create([
			'name' => $request->name,
			'password' => bcrypt($request->password)
		]);

		Phone::create([
			'user_id' => $user->id,
			'number' => $phoneNumber
		]);

		$this->guard()->setToken(
            $token = $this->guard()->login($user)
        );
		
		$expiration = $this->guard()->getPayload()->get('exp');

		return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiration - time(),
        ]);
	}

	public function login(Request $request)
	{
		$request->validate([
			'phone' => 'required|regex:/(\+)[0-9]{11}/',
			'password' => 'required|min:6',
		]);

		$phone = Phone::where('number', $request->phone)->firstOrFail();

		$token = $this->guard()->attempt(['id' => $phone->user_id, 'password' => $request->password]);

		if (! $token) {
            return response()->json([
				'status' => trans('firebase.user_not_found'),
			], 400);
        }

		$this->guard()->setToken($token);
        $expiration = $this->guard()->getPayload()->get('exp');

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiration - time(),
        ]);
	}
}
