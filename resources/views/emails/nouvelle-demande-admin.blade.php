@extends('emails.base')
@section('objet', 'Nouvelle demande à traiter')
@section('contenu')
    <p>Bonjour,</p>
    <p>Une nouvelle demande vient d'être déposée et <strong>attend son traitement</strong>.</p>
    <p>Email de l'usager : {{ $demande->email ?? 'non renseigné' }}</p>
@endsection
