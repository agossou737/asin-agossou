@extends('emails.base')
@section('objet', 'Demande validée')
@section('contenu')
    <p>Bonjour,</p>
    <p>Bonne nouvelle : votre demande a été <strong style="color:#198754;">validée</strong>.</p>
    <p>Le <strong>récapitulatif de votre demande</strong> est joint à cet email au format PDF. Vous pouvez également le télécharger à tout moment depuis la page de suivi, avec votre numéro de demande ou votre NPI.</p>
@endsection
