# -*- coding: utf-8 -*-
# Plugin do CTFd: tipo de flag "orion_equipe".
# Cada equipe tem a sua flag. O valor esperado é calculado na hora, a partir
# do segredo da arena + identidade da equipe que está submetendo + desafio.
# É o MESMO cálculo do alvo (PHP flag_equipe()):
#   FLAG{orion-<desafio>-HMAC12(SEGREDO, "equipe|desafio")}
# Assim, a flag de uma equipe é recusada para outra (anti-cola) e a submissão
# fica registrada em Submissions, apontando quem tentou usar flag alheia.
import hmac
import hashlib
import os

from CTFd.plugins.flags import BaseFlag, FLAG_CLASSES
from CTFd.utils.user import get_current_user, get_current_team


def _segredo() -> bytes:
    s = os.environ.get("ORION_TEAM_SECRET") or "troque-isto-no-env"
    return s.encode("utf-8")


def flag_equipe(equipe: str, desafio: str) -> str:
    equipe = (equipe or "").strip().lower()
    desafio = (desafio or "").strip().lower()
    h = hmac.new(_segredo(), f"{equipe}|{desafio}".encode("utf-8"), hashlib.sha256).hexdigest()[:12]
    return f"FLAG{{orion-{desafio}-{h}}}"


def _equipe_atual():
    # Modo "teams": usa o nome do time. Modo "users": usa o nome da conta (equipeNN).
    try:
        t = get_current_team()
        if t is not None and getattr(t, "name", None):
            return t.name
    except Exception:
        pass
    try:
        u = get_current_user()
        if u is not None and getattr(u, "name", None):
            return u.name
    except Exception:
        pass
    return None


class OrionEquipeFlag(BaseFlag):
    name = "orion_equipe"
    templates = {
        "create": "/plugins/orion_equipe/assets/create.html",
        "update": "/plugins/orion_equipe/assets/edit.html",
    }

    @staticmethod
    def compare(chal_key_flag, provided):
        # chal_key_flag.content guarda o identificador do desafio (ex.: "bola").
        desafio = (getattr(chal_key_flag, "content", "") or "").strip().lower()
        equipe = _equipe_atual()
        if not equipe:
            return False
        esperado = flag_equipe(equipe, desafio)
        fornecido = (provided or "").strip()
        return hmac.compare_digest(esperado, fornecido)


def load(app):
    FLAG_CLASSES["orion_equipe"] = OrionEquipeFlag
    try:
        from CTFd.plugins import register_plugin_assets_directory
        register_plugin_assets_directory(app, base_path="/plugins/orion_equipe/assets/")
    except Exception:
        pass
