<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des clients</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #4A4A4A;
            margin-bottom: 5px;
        }

        .description {
            text-align: center;
            margin-bottom: 30px;
            font-size: 12px;
            color: #666;
        }

        .generated-date {
            font-size: 10px;
            text-align: right;
            color: #888;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th,
        td {
            border: 1px solid #888;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>

<body>
    <h1>Liste des Clients</h1>
    <p class="description">
        Ce document contient la liste complète des clients enregistrés , avec leurs
        coordonnées et leur statut.
    </p>

    <div class="generated-date">
        Généré le : {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th>Statut</th>
                <th>Type</th>
                <th>Date de création</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->nom }}</td>
                    <td>{{ $client->prenom }}</td>
                    <td>{{ $client->telephone }}</td>
                    <td>{{ $client->adresse }}</td>
                    <td>{{ ucfirst($client->statut) }}</td>
                    <td>{{ ucfirst($client->type) }}</td>
                    <td>{{ \Carbon\Carbon::parse($client->created_at)->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>