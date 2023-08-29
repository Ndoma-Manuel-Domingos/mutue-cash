<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0">Perfil</h1>
          </div>
          <div class="col-sm-4">
            <button
              class="btn btn-dark float-right mr-1"
              type="button"
              @click="voltarPaginaAnterior"
            >
              <i class="fas fa-arrow-left"></i> VOLTAR A PÁGINA ANTERIOR
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
                <button
                  class="btn btn-info float-right"
                  type="button"
                  data-toggle="modal"
                  data-target="#modalCaixas"
                >
                  <i class="fas fa-plus"></i>
                  Novos Perfil
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nº</th>
                      <th>Perfil</th>
                      <th>Data criação</th>
                      <th>Data Actualização</th>
                      <th width="50px">Permissions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in roles.data" :key="item.codigo">
                      <td>{{ ++index }}</td>
                      <td>{{ item.name ?? "" }}</td>
                      <td>{{ item.created_at ?? "" }}</td>
                      <td>{{ item.updated_at ?? "" }}</td>
                      <td>
                        <a
                          class="btn-sm btn-info mx-1"
                          @click="adicionar_permission(item)"
                        >
                          <i class="fas fa-plus"></i>
                          Adicionar
                        </a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                  TOTAL REGISTROS: {{ roles.total }}
                </Link>
                <Paginacao
                  :links="roles.links"
                  :prev="roles.prev_page_url"
                  :next="roles.next_page_url"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL REGISTAR NOVO CAIXA  -->
    <div class="modal fade" id="modalCaixas">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">{{ formTitle }}</h4>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form @submit.prevent="submit">
            <div class="modal-body py-3">
              <div class="row">
                <div class="col-12 col-md-12 mb-3">
                  <div class="form-group">
                    <label for="" class="form-label"
                      >Designação do Perfil</label
                    >
                    <input
                      type="text"
                      v-model="form_role.name"
                      class="form-control"
                      placeholder="Ex: Operadpr"
                    />
                  </div>
                  <span class="text-danger d-block">{{
                    form_role.errors.name
                  }}</span>
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-black" data-dismiss="modal">
                Fechar
              </button>
              <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modelPermissions">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">
              Adicionar Permissões ao perfil de: {{ title }}
            </h4>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-hover text-nowrap">
                <thead>
                  <tr>
                    <th>Nº</th>
                    <th>Permissão</th>
                    <th>Data criação</th>
                    <th>Data Actualização</th>
                    <th width="50px" class="text-right">Acção</th>
                  </tr>
                </thead>
                <tbody>
                  <template
                    v-for="(item, index) in permissions.data"
                    :key="item.id"
                  >
                    <tr>
                      <td>{{ ++index }}</td>
                      <td>{{ item.name ?? "" }}</td>
                      <td>{{ item.created_at ?? "" }}</td>
                      <td>{{ item.updated_at ?? "" }}</td>
                      <td class="text-right">
                        <input
                          type="checkbox"
                          v-model="form_permissions.permissions_id"
                          style="width: 20px; height: 20px"
                          :value="item.id"
                        />
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-default" data-dismiss="modal">
              Fechar
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="actualizar_permissoes"
            >
              Actualizar Permissões
            </button>
          </div>
        </div>
      </div>
    </div>
    
    
  </MainLayouts>
</template>
    
  <script>
import { sweetSuccess, sweetError } from "../../../components/Alert";
import Paginacao from "../../../Shared/Paginacao";
import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: ["roles", "permissions"],
  components: { Link, Paginacao },
  data() {
    return {
      form_role: this.$inertia.form({
        name: "",
      }),

      form_permissions: this.$inertia.form({
        role_id: "",
        permissions_id: [],
      }),

      role: {},
      role_permissions: [],

      title: "",

      isUpdate: false,
      itemId: null,
    };
  },

  computed: {
    formTitle() {
      return this.isUpdate ? "Editar Perfil" : "Adicionar Perfil";
    },
  },

  mounted() {
    this.params.data_inicio = this.data_inicio;
  },

  watch: {
    options: function (val) {
      this.params.page = val.page;
      this.params.page_size = val.itemsPerPage;
      if (val.sortBy.length != 0) {
        this.params.sort_by = val.sortBy[0];
        this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
      } else {
        this.params.sort_by = null;
        this.params.order_by = null;
      }
      this.updateData();
    },
    operador: function (val) {
      this.params.operador = val;
      this.updateData();
    },
  },

  methods: {
   
    editarItem(item) {
      this.form_role.clearErrors();
      this.form_role.name = item.name;

      this.isUpdate = true;
      this.itemId = item.id;
      $("#modalCaixas").modal("show");
    },

    adicionar_permission(item) {
      
      this.title = item.name;
      this.form_permissions.role_id = item.id;

      this.$Progress.start();
      axios
        .get(`/roles/permissions/${item.id}`, {
          params: {},
        })
        .then((response) => {
          this.form_permissions.permissions_id = [];
          response.data.role.permissions.forEach(permission => {
            this.form_permissions.permissions_id.push(permission.id);
          });
          
          this.$Progress.finish();
        
        })
        .catch((error) => {
          this.$Progress.fail();
        });

      $("#modelPermissions").modal("show");
    },

    actualizar_permissoes() {
      this.form_permissions.post("/roles/adicionar-permissions", {
        preverseScroll: true,
        onSuccess: () => {
          this.form_permissions.reset();
          this.$Progress.finish();
          sweetSuccess("Dados salvos com sucesso");
          $("#modalCaixas").modal("hide");
          $("#modelPermissions").modal("hide");
        },
        onError: (errors) => {
          sweetError("Ocorreu um erro ao Cadastrar Perfil!");
        },
      });
    },

    submit() {
      this.$Progress.start();

      if (this.isUpdate) {
        this.form_role.put("/roles/update/" + this.itemId, {
          preverseScroll: true,
          onSuccess: () => {
            this.isUpdate = false;
            this.itemId = null;
            this.form_role.reset();

            this.$Progress.finish();
            sweetSuccess("Dados salvos com sucesso");
            $("#modalCaixas").modal("hide");
          },
          onError: (errors) => {
            sweetError("Ocorreu um erro ao actualizar Perfil!");
          },
        });
      } else {
        this.form_role.post("/roles/store", {
          preverseScroll: true,
          onSuccess: () => {
            this.form_role.reset();
            this.$Progress.finish();
            sweetSuccess("Dados salvos com sucesso");
            $("#modalCaixas").modal("hide");
          },
          onError: (errors) => {
            sweetError("Ocorreu um erro ao Cadastrar Perfil!");
          },
        });
      }
    },

    voltarPaginaAnterior() {
      window.history.back();
    },
  },
};
</script>
    
    
    