<?php

namespace App\Http\Controllers;

use App\ItineraryItem;
//use Debugbar;
use App\Itinerary;
use App\Passenger;
use App\User;
use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Auth;
use Image;
use Config;
use DB;
use Log;
use DateTime;
//use DebugBar;

class UserController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->middleware('auth');
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $Itineraries = Itinerary::where(['is_delete'=>0,'user_id'=>Auth::user()->id])->with([
            'itineraryItem'=> function($q){
//                $q->select('id','product_name','category_id');
                $q->where(['is_delete'=>0,'user_id'=>Auth::user()->id]);
            }
        ])->paginate(2);
        $uuid = str_random(10);
//        echo '<pre>';
//        print_r($Itineraries);
//
//        $Itineraries =$Itineraries->toArray();
//        echo '<pre>';
//        print_r($Itineraries); exit;

        return view('user.itinerary',['Itineraries' => $Itineraries, 'uuid'=>$uuid]);//-> with('Itineraries', $Itineraries)->with(compact('uuid'));;
//        return view('user.itinerary')-> with('Itineraries', $Itineraries)->with(compact('uuid'));;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        print_r($request->all());

//        $validator = Validator::make($request->all(), [
//            'itinerary_reference' => 'required|string',
//            'notes' => 'required|string|min:5|max:255',
//            'journey_date' => 'required|date|date_format:Y-m-d',
//            'cost' =>'required|numeric',
//
//        ]);
//        if ($validator->fails()) {
//            return response()->json(['status' => 'fail' ,'code'=>422 , 'errors'=> $validator->errors() ], 422);
////            return response()->json(['errors'=> $validator->errors() ], 422);
//        }


        $dataItinearyItems = [
            'user_id'=> Auth::user()->id,
            'passenger' => $request->passenger,
            'notes' =>$request->notes,
            'cost' =>$request->cost,
            'journey_date' => $request->journey_date,
//            'reference_image' => date('Y-m-d', strtotime($request->journey_date)),


        ];




        // Upload Image
        if ($request->file('reference_image') && $request->file('reference_image')->isValid()) {
            $image_tmp = $request->reference_image;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/itinerary_image/' . $filename;

                // Resize Images
                Image::make($image_tmp)->save($large_image_path);

                // Store image name in category table
                $dataItinearyItems['reference_image']  = $filename;
            }
        }else{

            $dataItinearyItems['reference_image']  = '800x600.png';

        }




        $itinerary_reference = Itinerary::where('itinerary_reference',$request->itinerary_reference)->first();

        if($itinerary_reference){

            $itinerary_reference->itineraryItem()->create($dataItinearyItems);

            $data = $itinerary_reference->itineraryItem;

        }else{

            $dataItineary = [

                'user_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'itinerary_reference' =>$request->itinerary_reference,

//                'journey_date' => date('Y-m-d', strtotime($request->journey_date)),


            ];

            $Itineary = Itinerary::create($dataItineary);

//            $bear = Bear::create(['Name' => $request->input('name_of_bear')]);
            $Itineary->itineraryItem()->create($dataItinearyItems);


            $data = $Itineary->itineraryItem;


        }




        return response()->json(
            [

                'status ' => "success",
                'data'=> $data,
                'code'=>200
            ]
            , 200
        );

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $itineraryId, $ItineraryItemID)
    {

//        print_r($request->all());

        $dataItinearyItems = [

            'passenger' => $request->passenger,
            'notes' =>$request->notes,
            'cost' =>$request->cost,
            'journey_date' => $request->journey_date,
//            'reference_image' => date('Y-m-d', strtotime($request->journey_date)),


        ];

        //print_r($dataItinearyItems); exit;


        // Upload Image
        if ($request->file('reference_image') && $request->file('reference_image')->isValid()) {
            $image_tmp = $request->reference_image;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/itinerary_image/' . $filename;

                // Resize Images
                Image::make($image_tmp)->save($large_image_path);

                // Store image name in category table
                $dataItinearyItems['reference_image']  = $filename;
            }
        }
//        else{
//
//            $dataItinearyItems['reference_image']  = '800x600.png';
//
//        }

        if(!empty($request->reference_image_delete)){

            $dataItinearyItems['reference_image']  = '800x600.png';

        }


//        $Itinerary = Itinerary::find($itineraryId);





        $Itinerary = Itinerary::find($itineraryId);

        if($Itinerary){

            $dataItineary = [

                'journey_date' => $request->journey_date,


            ];

            $Itinerary->update($dataItineary);

            $itineraryItem = ItineraryItem::find($ItineraryItemID);
//
            $itineraryItem->update($dataItinearyItems);
//
            $data = $Itinerary->itineraryItem;

        }




        return response()->json(
            [

                'status ' => "success",
                'data'=> $data,
                'code'=>200
            ]
            , 200
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($itineraryId, $ItineraryItemID)
    {
        $Itinerary = Itinerary::find($itineraryId);

        if($Itinerary){

            $dataItineary = [

                'is_delete'=>1,


            ];
            $dataItinearyItems = $dataItineary;

            $Itinerary->update($dataItineary);

            $itineraryItem = ItineraryItem::find($ItineraryItemID);
//
            $itineraryItem->update($dataItinearyItems);
//
            $data = $Itinerary->itineraryItem;

        }




        return response()->json(
            [

                'status ' => "success",
                'data'=> $data,
                'code'=>200
            ]
            , 200
        );
    }

    /**
     * Create a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function quote(Request $request, $uuid)
    {
//
        $uuid = $uuid;
        $Itineraries = Itinerary::where(['is_delete'=>0,'itinerary_reference'=>$uuid,'user_id'=>Auth::user()->id])->with('itineraryItem')->get();

//        Debugbar::info($Itineraries);

        return view('user.quote')->with(compact('uuid','Itineraries'));//->with('no', 1);
    }

    /**
     * Show a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function TeamMain()
    {

        return view('user.team_main');
    }

    /**
     * Verify Email a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function VerifyEmail()
    {

        return view('user.verify-email');
    }

    /**
     * Profile a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Profile()
    {
        $user = User::findOrFail(Auth::user()->id);

//        print_r($user); exit;

        return view('user.profile')->with(compact('user'));
    }


    /**
     * Profile a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function StoreProfile(Request $request, $id)
    {



        $validator = Validator::make($request->all(), [
            'gender' => 'required|string',
            'name' => 'required|string',
            'username' => 'required|string',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'zip_code' => 'required|numeric',
            'profile_picture' => 'image|mimes:jpg,jpeg,png',
//            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'fail' ,'code'=>422 , 'errors'=> $validator->errors() ], 422);
//            return response()->json(['errors'=> $validator->errors() ], 422);
        }

        $user = User::findOrFail($id);

        $data = [
//            'first_name' => $request->first_name,
//            'last_name' => $request->last_name,
//            'gender' => $request->gender,
//            'mobile_number' => $request->mobile_number,
//            'email' => $request->email,
//            'password' => Hash::make($request->password)

            'gender' => $request->gender,
            'name' => $request->name,
            'email' => Auth::user()->email,
            'username' => $request->username,
            'date_of_birth' => date('Y-m-d', strtotime($request->date_of_birth)),
            'address' => $request->address,
            'state' => $request->state,
            'city' => $request->city,
            'country' => $request->country,
            'zip_code' => $request->zip_code

        ];
        if(!empty($request->password) && ($request->password !='')){

            $data['password'] = Hash::make($request->password);

        }

        // Upload Image
        if ($request->file('profile_picture') && $request->file('profile_picture')->isValid()) {
            $image_tmp = $request->profile_picture;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/profileImage/large/' . $filename;
                $small_image_path = public_path() . '/profileImage/small/' . $filename;
                // Resize Images
                Image::make($image_tmp)->save($large_image_path);
                Image::make($image_tmp)->resize(40,40)->save($small_image_path);
                // Store image name in category table
                $data['profile_picture']  = $filename;
            }
        }



        $user->update($data);
//        $user = User::create($data);

        return response()->json(
            [

                'status ' => "success",
                'data'=> $user,
                'code'=>200
            ]
            , 200
        );

//        return view('user.profile');
    }


    /**
     * Payment a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Payment()
    {

        $payment = Payment::where('user_id', Auth::user()->id)->get();

//        print_r($payment); exit;

        return view('user.payment')->with(compact('payment'));

    }


    /**
     * Payment a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function StorePayment(Request $request, $id)
    {


        $validator = Validator::make($request->all(), [
            //'card_type' => 'required|string',
            'card_number' => 'required|string',
            'name' => 'required|string',
            'valid_thru' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'fail' ,'code'=>422 , 'errors'=> $validator->errors() ], 422);

        }

        $paymentExistCards = Payment::all()->toArray();

        foreach ($paymentExistCards as $paymentExistCard) {

            foreach ($paymentExistCard as $key => $value){

                if($key =='card_number'){

                    if(Crypt::decrypt($value) == $request->card_number){
                        return response()->json([
                            'status' => 'fail' ,
                            'code'=>422 ,
                            'errors'=> array('card_number'=>['This already exist']),
                        ], 422);
                    }
                     break;
                }
            }

        }

        $data = [

            'user_id' => $id,
            'card_number' => Crypt::encrypt($request->card_number),
            'name' => $request->name,
            'valid_thru' => Crypt::encrypt($request->valid_thru),

        ];

        if(!empty($request->card_type) && ($request->card_type !='')){
           if($request->card_type == "unknown") {
               $data['card_type'] = 'any';
           }else{
               $data['card_type'] = $request->card_type;
           }
        }else{
            $data['card_type'] = 'any';
        }

        if(!empty($request->is_primary) && ($request->is_primary !='')){

            DB::table('payments')->update(array('is_primary' => 0));
            $data['is_primary'] = $request->is_primary;

        }

        $payment = Payment::create($data);

        $payment->card_number = Crypt::decrypt($payment->card_number);
        $payment->valid_thru = Crypt::decrypt($payment->valid_thru);

        return response()->json(
            [
                'status ' => "success",
                'data'=> $payment,
                'code'=>200
            ]
            , 200
        );


    }

    public  function EditPayment(Request $request, $paymentId){

//        print_r($paymentId);



        $validator = Validator::make($request->all(), [
            //'card_type' => 'required|string',
            'card_number' => 'required|string',
            'name' => 'required|string',
            'valid_thru' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'fail' ,'code'=>422 , 'errors'=> $validator->errors() ], 422);

        }


        $paymentExistCards = Payment::whereNotIn('id', [$paymentId])->get()->toArray();

        foreach ($paymentExistCards as $paymentExistCard) {

            foreach ($paymentExistCard as $key => $value){

                if($key =='card_number'){

                    if(Crypt::decrypt($value) == $request->card_number){
                        return response()->json([
                            'status' => 'fail' ,
                            'code'=>422 ,
                            'errors'=> array('card_number'=>['This already exist']),
                        ], 422);
                    }
                    break;
                }
            }

        }

        $payment = Payment::findOrFail($paymentId);

        $data = [

            'user_id' => Auth::user()->id,
            'card_number' => Crypt::encrypt($request->card_number),
            'name' => $request->name,
            'valid_thru' => Crypt::encrypt($request->valid_thru),

        ];

        if(!empty($request->card_type) && ($request->card_type !='')){
            if($request->card_type == "unknown") {
                $data['card_type'] = 'any';
            }else{
                $data['card_type'] = $request->card_type;
            }
        }else{
            $data['card_type'] = 'any';
        }

        if($payment->is_primary){

            if(!$request->has('is_primary')){


                $data['is_primary'] = 0;

            }



        }else{

            if(!empty($request->is_primary) && ($request->is_primary !='')){

                DB::table('payments')->update(array('is_primary' => 0));
                $data['is_primary'] = $request->is_primary;

            }

        }

        $payment->update($data);

        return response()->json(
            [
                'status ' => "success",
                'data'=> $payment,
                'code'=>200
            ]
            , 200
        );


    }

    public function DeletePayment(Request $request, $paymentId){

        $payment =payment::destroy($paymentId);

        return response()->json(
            [
                'status ' => "success",
                'data'=> $paymentId,
                'code'=>200
            ]
            , 200
        );

    }

    /**
     * Passenger a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Passenger()
    {
        $passenger = Passenger::where('user_id', Auth::user()->id)->get();
        return view('user.passenger')->with(compact('passenger'));
    }


    /**
     * Payment a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function StorePassenger(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
//            'gender' => 'required|string',
//            'name' => 'required|string',
//            'surname' => 'required|string',
//            'date_of_birth' => 'required|date',
//            'expiry_date' => 'required|date',
//            'citizenship' => 'required|string',
//            'passport' => 'required|string',
            'passport_picture' => 'image|mimes:jpg,jpeg,png',
            'identity_picture' => 'image|mimes:jpg,jpeg,png',
//            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'fail' ,'code'=>422 , 'errors'=> $validator->errors() ], 422);

        }

        $data = [
            'user_id' => Auth::user()->id,
            'name' => $request->name,
            'surname' => $request->surname,
//            'gender' => $request->gender,
            'date_of_birth' => date('Y-m-d', strtotime($request->date_of_birth)),
            'expiry_date' => date('Y-m-d', strtotime($request->expiry_date)),
            'citizenship' => $request->citizenship,
            'passport' => $request->passport,
//            'city' => $request->city,
//            'country' => $request->country,
//            'zip_code' => $request->zip_code

        ];

        if(empty($request->gender)){

            $data['gender'] ='male';

        }else{

            $data['gender'] =$request->gender;

        }

        // Upload Image
        if ($request->file('passport_picture') && $request->file('passport_picture')->isValid()) {
            $image_tmp = $request->passport_picture;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/passport_picture/' . $filename;
                // Resize Images
                Image::make($image_tmp)->save($large_image_path);
                // Store image name in category table
                $data['passport_picture']  = $filename;
            }
        } else {
            $data['passport_picture']  = '';
        }

        if ($request->file('identity_picture') && $request->file('identity_picture')->isValid()) {
            $image_tmp = $request->identity_picture;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/identity_picture/' . $filename;
                // Resize Images
                Image::make($image_tmp)->save($large_image_path);
                // Store image name in category table
                $data['identity_picture']  = $filename;
            }
        } else {
            $data['identity_picture']  = '';
        }



        $passenger = Passenger::create($data);

        return response()->json(
            [
                'status ' => "success",
                'data'=> $passenger,
                'code'=>200
            ]
            , 200
        );


    }

    public  function EditPassenger(Request $request, $passportId){

//        print_r($paymentId);

//        print_r($request->all());
//
        $validator = Validator::make($request->all(), [
//            'gender' => 'required|string',
//            'name' => 'required|string',
//            'surname' => 'required|string',
//            'date_of_birth' => 'required|date',
//            'expiry_date' => 'required|date',
//            'citizenship' => 'required|string',
//            'passport' => 'required|string',
            'passport_picture' => 'image|mimes:jpg,jpeg,png',
            'identity_picture' => 'image|mimes:jpg,jpeg,png',
//            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'fail' ,'code'=>422 , 'errors'=> $validator->errors() ], 422);

        }

        $Passenger = Passenger::findOrFail($passportId);

        $data = [
            'user_id' => Auth::user()->id,
            'name' => $request->name,
            'surname' => $request->surname,
//            'gender' => $request->gender,
            'date_of_birth' => date('Y-m-d', strtotime($request->date_of_birth)),
            'citizenship' => $request->citizenship,
            'passport' => $request->passport,
//            'city' => $request->city,
//            'country' => $request->country,
//            'zip_code' => $request->zip_code

        ];


        if(empty($request->gender)){

            $data['gender'] ='male';

        }else{

            $data['gender'] =$request->gender;

        }

        // Upload Image
        if ($request->file('passport_picture') && $request->file('passport_picture')->isValid()) {
            $image_tmp = $request->passport_picture;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/passport_picture/' . $filename;
                // Resize Images
                Image::make($image_tmp)->save($large_image_path);
                // Store image name in category table
                $data['passport_picture']  = $filename;
            }
        }

        if(!empty($request->passport_picture_delete && !$request->has('passport_picture'))){

            $data['passport_picture']  = '';

        }

        if ($request->file('identity_picture') && $request->file('identity_picture')->isValid()) {
            $image_tmp = $request->identity_picture;
            if ($image_tmp->isValid()) {
                $extension = $image_tmp->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $large_image_path = public_path() . '/identity_picture/' . $filename;
                // Resize Images
                Image::make($image_tmp)->save($large_image_path);
                // Store image name in category table
                $data['identity_picture']  = $filename;
            }
        }

        if(!empty($request->identity_picture_delete && !$request->has('identity_picture'))){

            $data['identity_picture']  = '';

        }

        $Passenger->update($data);

        return response()->json(
            [
                'status ' => "success",
                'data'=> $Passenger,
                'code'=>200
            ]
            , 200
        );


    }

    public function DeletePassenger(Request $request, $passportId){

        $Passenger = Passenger::destroy($passportId);

        return response()->json(
            [
                'status ' => "success",
                'data'=> $passportId,
                'code'=>200
            ]
            , 200
        );

    }



    /**
     * Timeline a Quote of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Timeline(Request $request)
    {
        $itineraryID = $request->query('id');

        $Itineraries = Itinerary::where(['status'=>4,'is_delete'=>0,'id'=>$itineraryID,'user_id'=>Auth::user()->id])->with('itineraryItem')->get();

//        echo '<pre>';
//        print_r($Itineraries);
//        exit;

        return view('user.timeline',['Itineraries' => $Itineraries]);//->with(compact('Itineraries'));

    }
}
