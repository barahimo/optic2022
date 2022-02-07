<?php

namespace App\Http\Controllers;

use App\Demande;
use App\Fournisseur;
use App\Payement;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayementController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        $this->middleware('statususer');
    }
    /**
    *--------------------------------------------------------------------------
    * Mes Fonctions
    *--------------------------------------------------------------------------
    **/
    public function getPermssion($string){
        $list = [];
        $array = explode("','",$string);
        foreach ($array as $value) 
            foreach (explode("['",$value) as $val) 
                if($val != '')
                    array_push($list, $val);
        $array = $list;
        $list = [];
        foreach ($array as $value) 
            foreach (explode("']",$value) as $val) 
                if($val != '')
                    array_push($list, $val);
        return $list;
    }

    public function hasPermssion($string){
        $permission = $this->getPermssion(Auth::user()->permission);
        $permission_ = $this->getPermssion(User::find(Auth::user()->user_id)->permission);
        $result = 'no';
        if(
            (Auth::user()->is_admin == 2) ||
            (Auth::user()->is_admin == 1 && in_array($string,$permission)) ||
            (Auth::user()->is_admin == 0 && in_array($string,$permission) && in_array($string,$permission_))
        )
        $result = 'yes';
        return $result;
    }
    /**
    * --------------------------------------------------------------------------
    *  store2
    * --------------------------------------------------------------------------
    **/
    // Enregistrer plusieurs payements
    public function store2(Request $request){ 
        $user_id = Auth::user()->id;
        if(Auth::user()->is_admin == 0)
            $user_id = Auth::user()->user_id;
        $lignes = $request->input('lignes');
        if(!empty($lignes)){
            $date = $request->input('date');
            $fournisseur = $request->input('fournisseur');
            $mode = $request->input('mode');
            // -----------------------------------------------------
            $time = strtotime($date);
            $year = date('y',$time);
            $month = date('m',$time);
            // -----------------------------------------------------
            if(!empty($date) && !empty($fournisseur) && $mode != ""){
                // ------------ Begin payement -------- //
                foreach ($lignes as $ligne) {
                    $payement = new Payement();
                    $payement->date = $date;
                    $payement->mode_payement = $mode;
                    $payement->avance = $ligne['avance'];
                    $payement->reste = $ligne['reste'];
                    $payement->status = $ligne['status'];
                    // -----------------------------------------------------
                    $payements = Payement::where('user_id',$user_id)->get();
                    (count($payements)>0) ? $lastcode = $payements->last()->code : $lastcode = null;
                    $str = 1;
                    if(isset($lastcode)){
                        // ----- RF-2108-0001 ----- //
                        $list = explode("-",$lastcode);
                        $y = substr($list[1],0,2);
                        $n = $list[2];
                        ($y == $year) ? $str = $n+1 : $str = 1;
                    } 
                    $pad = str_pad($str,4,"0",STR_PAD_LEFT);
                    $code = 'RF-'.$year.''.$month.'-'.$pad;
                    // -----------------------------------------------------
                    $payement->code = $code;
                    $payement->demande_id = $ligne['cmd_id'];
                    $payement->user_id = $user_id;
                    $payement->save();
                    
                    $demande = Demande::find($ligne['cmd_id']);
                    $demande->avance = $demande->avance+$ligne['avance'];
                    $demande->reste = $ligne['reste'];
                    $demande->user_id = $user_id;
                    $demande->save();
                }
                // ------------ End payement -------- //
            } 
            else{
                return ['status'=>"error",'message'=>"Veuillez remplir les champs vides !"];
            }
        }
        else {
            return ['status'=>"error",'message'=>"Veuillez d'ajouter des payements"];
        }

        return ['status'=>"success",'message'=>"Le paiement a été bien enregistré !!"];
    }
    /**
    * --------------------------------------------------------------------------
    *  store3
    * --------------------------------------------------------------------------
    **/
    // Enregistrer une seule payement
    public function store3(Request $request){ 
        $user_id = Auth::user()->id;
        if(Auth::user()->is_admin == 0)
            $user_id = Auth::user()->user_id;
        $lignes = $request->input('lignes');
        if(!empty($lignes)){
            $date = $request->input('date');
            $mode = $request->input('mode');
            // -----------------------------------------------------
            $time = strtotime($date);
            $year = date('y',$time);
            $month = date('m',$time);
            // -----------------------------------------------------
            $payements = Payement::where('user_id',$user_id)->get();
            (count($payements)>0) ? $lastcode = $payements->last()->code : $lastcode = null;
            $str = 1;
            if(isset($lastcode)){
                // ----- RF-2108-0001 ----- //
                $list = explode("-",$lastcode);
                $y = substr($list[1],0,2);
                $n = $list[2];
                ($y == $year) ? $str = $n+1 : $str = 1;
            } 
            $pad = str_pad($str,4,"0",STR_PAD_LEFT);
            $code = 'RF-'.$year.''.$month.'-'.$pad;
            // -----------------------------------------------------
            if(!empty($date) && $mode != ""){
                // ------------ Begin payement -------- //
                foreach ($lignes as $ligne) {
                    $payement = new Payement();
                    $payement->date = $date;
                    $payement->mode_payement = $mode;
                    $payement->avance = $ligne['avance'];
                    $payement->reste = $ligne['reste'];
                    $payement->status = $ligne['status'];
                    $payement->code = $code;
                    $payement->demande_id = $ligne['cmd_id'];
                    $payement->user_id = $user_id;
                    $payement->save();
                    
                    $demande = Demande::find($ligne['cmd_id']);
                    $demande->avance = $demande->avance+$ligne['avance'];
                    $demande->reste = $ligne['reste'];
                    $demande->user_id = $user_id;
                    $demande->save();
                }
                // ------------ End payement -------- //
            } 
            else{
                return ['status'=>"error",'message'=>"Veuillez remplir les champs vides !"];
            }
        }
        else {
            return ['status'=>"error",'message'=>"Veuillez d'effectuer le paiement"];
        }
    
        return ['status'=>"success",'message'=>"Le paiement a été bien enregistré !!"];
    }
    /**
    * --------------------------------------------------------------------------
    *  avoir
    * --------------------------------------------------------------------------
    **/
    public function avoir(Request $request){ 
        $data = $request->input('obj');

        $reg_id = $data['reg_id']; 
        $reg_avance = $data['reg_avance'];
        $reg_reste = $data['reg_reste'];
        $cmd_id = $data['cmd_id'];
        $cmd_avance = $data['cmd_avance'];
        $cmd_reste = $data['cmd_reste'];

        $user_id = Auth::user()->id;
        if(Auth::user()->is_admin == 0)
            $user_id = Auth::user()->user_id;

        $payement = Payement::where('user_id',$user_id)->find($reg_id);
        $payement->avance = $reg_avance + $reg_reste;
        $payement->reste = $reg_reste - $reg_reste;
        $payement->status = 'R';
        $payement->save();
        $demande = Demande::where('user_id',$user_id)->find($cmd_id);
        $demande->avance = $cmd_avance + $reg_reste;
        $demande->reste = $cmd_reste - $reg_reste;;
        $demande->save();

        return ['status'=>"success",'message'=>"Opération effectuée avec succés !!!"];
    }
    /**
    * --------------------------------------------------------------------------
    *  getpayements3
    * --------------------------------------------------------------------------
    **/
    public function getPayements3(Request $request){
        $user_id = Auth::user()->id;
        if(Auth::user()->is_admin == 0)
            $user_id = Auth::user()->user_id;
        $fournisseur = $request->fournisseur;
        if($fournisseur){
            $nom_fournisseur = Fournisseur::find($fournisseur)->nom_fournisseur;
            $demandes = Demande::with(['fournisseur','payements'])
                        ->whereHas('fournisseur',function($query) use ($nom_fournisseur){
                            $query->where('nom_fournisseur',$nom_fournisseur);
                        })
                        ->where('reste', '>', 0)
                        ->where('user_id',$user_id)
                        ->orderBy('id','desc')
                        ->get();
        }
        else{
            $demandes = Demande::with(['fournisseur','payements'])
                        ->where('reste', '>', 0)
                        ->where('user_id',$user_id)
                        ->orderBy('id','desc')
                        ->get();
        }
        return response()->json($demandes);
    }
    /**
    * --------------------------------------------------------------------------
    *  create2
    * --------------------------------------------------------------------------
    **/
    //regler plusieurs demandes 
    public function create2(Request $request){
        $user_id = Auth::user()->id;
        if(Auth::user()->is_admin == 0)
            $user_id = Auth::user()->user_id;
        $fournisseurs = Fournisseur::where('user_id',$user_id)->get();
        $fournisseur = $request->fournisseur;
        $date = Carbon::now();
        if($fournisseur){
            $nom_fournisseur = Fournisseur::find($fournisseur)->nom_fournisseur;
            $demandes = Demande::with('fournisseur')->whereHas('fournisseur',function($query) use ($nom_fournisseur){
                        $query->where('nom_fournisseur',$nom_fournisseur);
                    })
                    ->where('reste', '>', 0)
                    ->where('user_id',$user_id)
                    ->orderBy('id','desc')
                    ->get();
        }
        else{
            $demandes = Demande::with('fournisseur')
                ->where('user_id',$user_id)
                ->where('reste', '>', 0)
                ->orderBy('id','desc')
                ->get();
        }
        $permission = $this->getPermssion(Auth::user()->permission);
        if($this->hasPermssion('create5_2') == 'yes')
        return view('managements.payements.create2',compact('fournisseurs','fournisseur','demandes','date'));
        else
        return redirect()->back();
    }
    /**
    * --------------------------------------------------------------------------
    *  create3
    * --------------------------------------------------------------------------
    **/
    //Regler une seule demande
    public function create3(Request $request){
        $demande_id = $request->demande;
        $date = Carbon::now();
        $user_id = Auth::user()->id;
        if(Auth::user()->is_admin == 0)
            $user_id = Auth::user()->user_id;
        $demande = Demande::with('fournisseur')->where('user_id',$user_id)->findOrFail($demande_id);
        $permission = $this->getPermssion(Auth::user()->permission);
        if($this->hasPermssion('create5_2') == 'yes')
        return view('managements.payements.create3',compact('demande','date'));
        else
        return redirect()->back();
    }
    /**
    * --------------------------------------------------------------------------
    *  delete
    * --------------------------------------------------------------------------
    **/
    public function delete($id){
        $payement = Payement::find($id);
        $demande = Demande::find($payement->demande_id);
        $demande->avance = $demande->avance - $payement->avance;
        $demande->reste = $demande->total - $demande->avance;
        $demande->save();
        $payement->delete();
        $count = $demande->total;
        $payements = Payement::where('demande_id',$demande->id)->get();
        foreach ($payements as $payement) {
            $payement->reste = $count - $payement->avance;
            ($payement->reste > 0) ? $payement->status = 'NR' : $payement->status = ' R' ;
            $count = $payement->reste;
            $payement->save();
        }

        return redirect()->route('demande.index')->with([
            "success" => "Le paiement a été supprimé avec succès !"
        ]); 
    }
    /**
    *--------------------------------------------------------------------------
    * Ressources
    *--------------------------------------------------------------------------
    **/
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
