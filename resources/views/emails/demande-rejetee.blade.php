@extends('emails.base')
@section('objet', 'Demande rejetée')
@section('contenu')
    <p>Bonjour,</p>
    <p>Nous sommes au regret de vous informer que votre demande a été <strong style="color:#dc3545;">rejetée</strong>.</p>
    <p><strong>Motif :</strong> {{ $demande->motif_rejet }}</p>
@endsection
