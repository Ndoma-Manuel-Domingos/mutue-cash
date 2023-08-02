<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Novo Pagamento</h1>
          </div>
          <div class="col-sm-6"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="card">
              <div class="card-header">
                <h6>Pesquiar Estudantes</h6>
              </div>

              <div class="card-body">
                <form action="">
                  <div class="row">
                    <div class="col-12 col-md-12 mb-3">
                      <div class="input-group">
                        <input
                          class="form-control"
                          type="search"
                          v-model="codigo_matricula"
                          placeholder="Ex: 54633"
                          aria-label="Search"
                        />
                        <div class="input-group-append">
                          <button
                            class="btn btn-info"
                            @click="pesqisar_estudante"
                          >
                            <i class="fas fa-search fa-fw"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <div class="card" v-show="mostrar_dados_estudante" v-if="nome_estudante != null">
              <div class="card-header">
                <h6>Dados do Estudantes</h6>
              </div>

              <div class="card-body">
                <table
                  id="example1"
                  style="width: 100%"
                  class="table-sm table-bordered table-striped table-responsive-sm table-responsive-md table-responsive-lg table-responsive-xl table-responsive-xxl"
                >
                  <thead>
                    <tr>
                      <th>Nº</th>
                      <th>Nome Completo</th>
                      <th>Bilhete</th>
                      <th>Saldo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>{{ codigo_matricula }}</td>
                      <td>{{ nome_estudante }}</td>
                      <td>{{ bilheite_estudante }}</td>
                      <td>{{ formatPrice(saldo_aluno) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="card" v-if="nome_estudante != null">
              <div class="card-header">
                <div class="row" v-if="prestacoes_por_ano > 0">
                  <div class="col-12 col-md-6">
                    <h6>Última prestação paga:</h6>
                    <!-- + -->
                    <span v-if="anoLectivo.Designacao <= 2019">
                      {{ ultima_prestacao_antiga_paga }}ª de {{ prestacoes_por_ano }}
                    </span>
                    <span v-else>
                      {{ ultima_prestacao_paga }}ª de {{ prestacoes_por_ano }}
                    </span>
                  </div>

                  <div class="col s12 m6" v-if="bolseiro" style="float: right">
                    <template v-if="bolseiro.desconto == 100">
                      <b
                        >{{ this.estudante_tipo4.designacao }} ({{
                          estudante_tipo4.descricao
                        }})</b
                      >
                    </template>
                    <template
                      v-else-if="
                        bolseiro.desconto < 100 &&
                        bolseiro.codigo_tipo_bolsa != 32
                      "
                    >
                      <b
                        >{{ this.estudante_tipo3.designacao }} ({{
                          estudante_tipo3.descricao
                        }}
                        - {{ bolseiro.desconto }}%)</b
                      >
                    </template>
                    <template
                      v-else-if="
                        bolseiro.desconto < 100 &&
                        bolseiro.codigo_tipo_bolsa == 32
                      "
                    >
                      <b style="font-size: 10;">{{ this.estudante_tipo2.designacao }} ({{
                          estudante_tipo2.descricao
                        }}
                        - {{ bolseiro.desconto }}%)</b
                      >
                    </template>
                  </div>
                  <div
                    class="col s12 m6"
                    v-else-if="cadeiras >= 0 && cadeiras <= 3"
                    style="float: right"
                  >
                    <b
                      >{{ estudante_tipo2.designacao }} ({{
                        estudante_tipo2.descricao
                      }}
                      - 50%)</b
                    >
                  </div>
                  <div
                    class="col s12 m6"
                    v-else-if="desconto_especial_nov21_jul22 > 0"
                    style="float: right"
                  >
                    <b
                      >{{ estudante_tipo2.designacao }} ({{
                        estudante_tipo2.descricao
                      }}
                      - {{ desconto_incentivo }}%)</b
                    >
                  </div>
                  <div class="col s12 m6" v-else style="float: right">
                    <b
                      >{{ estudante_tipo1.designacao }} ({{
                        estudante_tipo1.descricao
                      }})</b
                    >
                  </div>

                  <div
                    class="col s12 m6"
                    v-if="
                      add_servico.TipoServico == 'Mensal' &&
                      cadeiras > 3 &&
                      !bolseiro
                    "
                  >
                    <b style="float: left">{{ info_desconto_ano_todo }}</b
                    ><br />
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-md-12 mt-2 mb-2">
                    <div class="input-group">
                      <select
                        class="form-control"
                        :disabled="isFormDisabled"
                        @change="faturaByReference"
                        v-model="numero_fatura_nao_paga"
                      >
                        <option disabled>Selecione Facturas a Pagar</option>
                        <option
                          v-for="item in referencias_nao_pagas"
                          :key="item.codigo_fatura"
                          :value="item.codigo_fatura"
                        >
                          <span
                            v-if="item.mes_temp > 0 && item.tipo_fatura != 5"
                            >Propinas ({{ item.codigo_fatura }})</span
                          >
                          <span v-else-if="item.tipo_fatura == 5"
                            >Negociação de dívidas ({{
                              item.codigo_fatura
                            }})</span
                          >
                          <span v-else-if="item.codigo_tipo_avaliacao == 7"
                            >Inscrição de Recursos ({{
                              item.codigo_fatura
                            }})</span
                          >
                          <span v-else-if="item.codigo_tipo_avaliacao == 22"
                            >Inscrição de Melhorias ({{
                              item.codigo_fatura
                            }})</span
                          >
                          <span v-else-if="item.codigo_tipo_avaliacao == 11"
                            >Inscrição de Exame Especial ({{
                              item.codigo_fatura
                            }})</span
                          >
                          <span v-else-if="item.tipo_fatura == 3"
                            >Inscrição de Cadeiras ({{
                              item.codigo_fatura
                            }})</span
                          >
                          <span v-else
                            >Outros Serviços ({{ item.codigo_fatura }})</span
                          >
                        </option>
                      </select>
                    </div>
                  </div>
                </div>

                <form id="form" @submit.prevent="adicionarMeses">
                  <div class="row" v-if="fatura.ValorAPagar <= 0 || numero_fatura_nao_paga == -1">

                    <div class="col-12 col-md-6 mb-3">
                      <label for="" class="form-label">Serviço a pagar</label>
                      <select class="form-control" v-model="opcoes" required="" @change="AllClean">
                        <option value="1" v-if="(bolseiro && bolseiro.desconto != 100) || !bolseiro">
                          Propina
                        </option>
                        <option value="2">Outros Serviços</option>
                      </select>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                      <label for="" class="form-label">Ano Lectivo</label>
                      <select class="form-control" v-model="anoLectivo" @change="pegaPropina(), getPrestacoes()">
                        <option disabled value="">Seleccione o ano</option>
                        <template v-if="codigo_tipo_candidatura == 1">
                          <option v-for="ano in anosLectivos" :value="ano" :key="ano.Codigo" >
                            {{ ano.Designacao }}
                          </option>
                        </template>
                        <template v-else-if="codigo_tipo_candidatura ==2 && opcoes == 1">
                          <option v-for="ano in ciclo_mestrado" :value="ano" :key="ano.Codigo">
                            {{ano.Designacao}}
                          </option>
                        </template>
                        <template  v-else-if="codigo_tipo_candidatura ==3 && opcoes == 1">
                          <option v-for="ano in ciclo_doutoramento" :value="ano" :key="ano.Codigo">
                            {{ano.Designacao}}
                          </option>
                        </template>
                        <template  v-else-if="(codigo_tipo_candidatura ==2 || codigo_tipo_candidatura ==3) && opcoes == 2">
                          <option v-for="ano in anosLectivos" :value="ano" :key="ano.Codigo">
                            {{ano.Designacao}}
                          </option>
                        </template>
                      </select>
                    </div>
                    <div class="col-12 col-md-12 mb-3" v-if=" opcoes == 2">
                      <label for="" class="form-label">Serviços</label>
                      <select
                        :disabled="!anoLectivo"
                        v-model="add_servico"
                        class="form-control"
                      >
                        <option disabled value="">--opções--</option>
                        <option
                          v-for="servico in servicos"
                          v-bind:value="servico"
                          :key="servico"
                        >
                          {{ servico.Descricao }}
                        </option>
                      </select>
                    </div>


                  </div>

                  <div class="row">
                    <div class="col-12 col-md-6" v-if="fatura.ValorAPagar <= 0 || numero_fatura_nao_paga == -1">
                      <template
                        v-if="opcoes == 1 || add_servico.TipoServico == 'Mensal'"
                      >
                        <button type="submit" v-if="+anoLectivo.Designacao <= 2019"
                          :disabled="(!estudante.Nome_Completo && !estudante.curso_designacao) || tabela.length + todos_meses_pagos >= meses.length"
                          class="btn-sm btn-success m-1"
                        >
                          <i class="fas fa-plus"></i> ADICIONAR
                        </button>


                        <button type="submit" v-else :disabled="(!estudante.Nome_Completo && !estudante.curso_designacao) ||
                          tabela.length + todos_meses_pagos >= +meses_temp_lista.length"
                          class="btn-sm btn-success m-1"
                        >
                          <i class="fas fa-plus"></i> ADICIONAR
                        </button>

                      </template>

                      <template v-else>
                        <button
                          :disabled="!estudante.Nome_Completo && !estudante.curso_designacao"
                          type="submit"
                          class="btn btn-sm btn-success m-1"
                        >
                        <i class="fas fa-plus"></i>Adicionar
                        </button>
                      </template>

                      <button type="button" v-if="tabela.length != 0 " @click="AllClean" class="btn-sm btn-danger m-1">
                        <i class="fas fa-trash"></i> REMOVER
                      </button>
                    </div>

                    <!-- table para carregar as prestações -->

                    <table
                      id="example1"
                      v-if="fatura.ValorAPagar <= 0 || numero_fatura_nao_paga == -1"
                      style="width: 100%"
                      class="table-sm table-bordered table-striped table-responsive-sm table-responsive-md table-responsive-lg table-responsive-xl table-responsive-xxl mt-4"
                    >
                      <thead>
                        <tr>
                          <td colspan="7" class="text-info">
                            TOTAL A PAGAR: {{ formatPrice(total_adicionado) }}
                          </td>
                        </tr>

                        <tr>
                          <th>Nº</th>
                          <th>Serviço</th>
                          <th>{{opcoes == 1 || add_servico.TipoServico == "Mensal" ? "Prestação": " "}}</th>
                          <th>Valor</th>
                          <th>Multa</th>
                          <th>Desconto</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, key) in tabela">
                          <td>{{ ++key }}</td>
                          <td>{{ item.Descricao }}</td>
                          <!--td>{{item.Mes}}</td-->

                          <td v-if="item.Mes == 'MAR-2020'">{{ item.Mes }}</td>
                          <td v-else-if="item.Mes == 'JUL-2020'">{{ item.Mes }}</td>
                          <td v-else-if="item.Mes == 'AGO-2020'">{{ item.Mes }}</td>
                          <td v-else-if="item.Mes == 'SET-2020'">{{ item.Mes }}</td>

                          <td
                            v-else-if="
                              opcoes == 2 || add_servico.TipoServico != 'Mensal'
                            "
                            class="center"
                          >
                            &nbsp;
                          </td>
                          <td v-else>
                            <template v-if="+anoLectivo.Designacao <= 2019">
                              {{ ultima_prestacao_antiga_paga + key }}ª de
                              {{ prestacoes_por_ano }}
                            </template>
                            <template v-else>
                              {{ ultima_prestacao_paga + key }}ª de
                              {{ prestacoes_por_ano }}
                            </template>
                          </td>

                          <td>{{ formatPrice(item.Preco) }}</td>
                          <td>{{ formatPrice(item.Multa) }}</td>
                          <td>{{ formatPrice(item.Desconto) }}</td>
                          <td>{{ formatPrice(item.Total) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>


                </form>

                <!-- esta tabela aparece quando a valor da factura será mairo do que 0 -->
                <table
                  id="example1"
                  v-if="fatura.ValorAPagar > 0"
                  style="width: 100%"
                  class="table-sm table-bordered table-striped table-responsive-sm table-responsive-md table-responsive-lg table-responsive-xl table-responsive-xxl mt-4"
                >
                  <thead>
                    <tr>
                      <th colspan="3" class="text-info">
                        TOTAL A PAGAR: {{ formatPrice(fatura.ValorAPagar) }} ({{
                          extenso
                        }})
                      </th>
                    </tr>

                    <tr>
                      <th>Nº</th>
                      <th>Serviço</th>
                      <th title="Quantidade">Qtd</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in itens" :key="item">
                      <td>{{ index + 1 }}</td>

                      <td
                        v-if="
                          fatura.descricao_factura != 3 &&
                          fatura.descricao_factura != 6 &&
                          fatura.descricao_factura != 7 &&
                          fatura.descricao_factura != 8
                        "
                      >
                        <span
                          v-if="
                            fatura.descricao_factura != 5 &&
                            item.servico.match('Propina')
                          "
                          >Propina</span
                        >
                        <span
                          v-else-if="
                            fatura.descricao_factura != 5 &&
                            !item.servico.match('Propina')
                          "
                          >Outros serviços</span
                        >
                        <span v-else-if="fatura.descricao_factura == 5"
                          >Negociação de dívida</span
                        >
                        <!--span v-else>{{ item.servico }}</span-->
                      </td>

                      <td v-else-if="fatura.descricao_factura == 6">
                        <span>Inscrição de recurso</span>
                      </td>

                      <td v-else-if="fatura.descricao_factura == 7">
                        <span>Inscrição de melhoria de nota</span>
                      </td>

                      <td v-else-if="fatura.descricao_factura == 8">
                        <span>Inscrição de exame de especial</span>
                      </td>

                      <td v-else-if="fatura.descricao_factura == 3">
                        <span>Inscrição de cadeiras</span>
                      </td>

                      <td>{{ item.qtd }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th>Valor a Pagar</th>
                      <th>Valor Entregue</th>
                      <th>Valor em Falta</th>
                    </tr>

                    <tr>
                      <td>{{ formatPrice(fatura.ValorAPagar) }}</td>
                      <td>
                        <span v-if="fatura.ValorEntregue < valor_pagamentos">{{
                          formatPrice(valor_pagamentos)
                        }}</span>
                        <span v-else>{{
                          formatPrice(fatura.ValorEntregue)
                        }}</span>
                      </td>
                      <td>
                        <span v-if="fatura.ValorEntregue < valor_pagamentos">
                          <span
                            v-if="fatura.ValorAPagar - valor_pagamentos < 0"
                            >{{ formatPrice(0) }}</span
                          >
                          <span v-else>{{
                            formatPrice(fatura.ValorAPagar - valor_pagamentos)
                          }}</span>
                        </span>
                        <span v-else>
                          <span
                            v-if="fatura.ValorAPagar - fatura.ValorEntregue < 0"
                            >{{ formatPrice(0) }}</span
                          >
                          <span v-else>{{
                            formatPrice(
                              fatura.ValorAPagar - fatura.ValorEntregue
                            )
                          }}</span>
                        </span>
                      </td>
                    </tr>

                    <tr v-if="fatura.descricao_factura == 5">
                      <td colspan="3" v-if="tipo_negociacao_id == 2">
                        <strong
                          >Valor 100% da dívida:
                          {{ formatPrice(metadeValorPagar) }}</strong
                        >
                      </td>
                      <td colspan="3" v-else>
                        <strong
                          >Valor 50% da dívida:
                          {{ formatPrice(metadeValorPagar) }}</strong
                        >
                      </td>
                    </tr>

                    <tr v-if="fatura.ValorAPagar > 0">
                      <td colspan="3">
                        TOTAL A PAGAR: {{ formatPrice(fatura.ValorAPagar) }} ({{
                          extenso
                        }})
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <div class="card-footer"></div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="card">
              <div class="card-header">
                <h6>Dados do Pagamento</h6>
              </div>

              <div class="card-body"
                :disabled="tabela.length == 0 && fatura.ValorAPagar <= 0"
              >
                <table
                    v-if="estudante.saldo > 0 && (estudante.saldo >= total_adicionado || estudante.saldo >=  fatura.ValorAPagar)"
                    id="example1"
                    style="width: 100%"
                    class="table-sm table-bordered table-striped table-responsive-sm table-responsive-md table-responsive-lg table-responsive-xl table-responsive-xxl"
                >
                  <thead>
                    <tr>
                      <th>Saldo disponível</th>
                      <th>Valor em falta</th>
                      <th>valor a pagar</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>{{ formatPrice(estudante.saldo) }}</td>
                      <td v-if="estudante.saldo >= total_adicionado">
                        {{ formatPrice(0)}}
                      </td>
                      <td v-else>{{ formatPrice(valor_por_depositar) }}</td>
                      <td>{{ formatPrice(total_adicionado) }}</td>
                    </tr>
                  </tbody>
                </table>
                <form
                  v-if="estudante.saldo < total_adicionado || estudante.saldo < (fatura.ValorAPagar - valor_pagamentos)"
                  action=""
                  id="formulario_pagamento"
                  @submit.prevent="validar"
                  enctype="multipart/form-data"
                >
                  <div class="row">
                    <!-- <div class="col-12 col-md-6 mb-3">
                      <label for="" class="form-label"
                        >Forma de Pagamento</label
                      >
                      <select
                        v-model="pagamento.forma_pagamento"
                        @change="pegaBancos"
                        id="forma_pagamento"
                        class="form-control"
                        :disabled="isFormDisabled"
                      >
                        <option value="">Selecione</option>
                        <option
                          :value="item.descricao"
                          v-for="item in forma_pagamentos"
                          :key="item.Codigo"
                        >
                          {{ item.descricao }}
                        </option>
                      </select>
                    </div>

                    <div
                      class="col-12 col-md-6 mb-3"
                      v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                    >
                      <label for="" class="form-label"
                        >Contas Movimentada</label
                      >
                      <select
                        v-model="pagamento.ContaMovimentada"
                        id="conta_movimentada"
                        class="form-control"
                        :disabled="isFormDisabled"
                      >
                        <option
                          :value="item.codigo"
                          v-for="(item, index) in bancos"
                          :key="index"
                        >
                          {{ item.descricao }}
                        </option>
                      </select>
                    </div> -->

                    <!-- <div
                      class="col-12 col-md-6 mb-3"
                      v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                    >
                      <label for="" class="form-label"
                        >Nº Operação de Bancária</label
                      >
                      <input
                        type="text"
                        v-model="pagamento.N_Operacao_Bancaria"
                        class="form-control"
                        placeholder="Ex: 12346"
                        :disabled="isFormDisabled"
                      />
                    </div>

                    <div
                      class="col-12 col-md-6 mb-3"
                      v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                    >
                      <label for="" class="form-label"
                        >Nº Operação de Bancária 2</label
                      >
                      <input
                        type="text"
                        v-model="pagamento.N_Operacao_Bancaria2"
                        class="form-control"
                        placeholder="Ex: 12346"
                        :disabled="isFormDisabled"
                      />
                    </div> -->

                    <!-- <div
                      class="col-12 col-md-6 mb-3"
                      v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                    >
                      <label for="" class="form-label">Data do Banco</label>
                      <input
                        type="date"
                        v-model="pagamento.DataBanco"
                        class="form-control"
                        :disabled="isFormDisabled"
                      />
                    </div> -->

                    <div
                      class="col-12 col-md-6 mb-3">
                      <label for="" class="form-label">Valor entregue</label>
                      <input
                        type="text"
                        class="form-control"
                        v-model="pagamento.valor_depositado"
                        placeholder="Ex: 12346"
                      />
                      <!-- <input
                        v-if="ativar_editar_valor == 1"
                        type="text"
                        class="form-control"
                        v-model="pagamento.valor_depositado"
                        placeholder="Ex: 12346"
                      />
                      <input
                        :readonly="ativar_editar_valor == 0"
                        v-else
                        class="form-control"
                        type="text"
                        v-model="pagamento.valor_depositado"
                        :disabled="ativar_editar_valor == 0"
                        placeholder="Ex: 12346"
                      /> -->
                    </div>

                    <!-- <div class="col-12 col-md-4 mb-3">
                      <label
                        for=""
                        class="form-label"
                        v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                        >Anexo</label
                      >
                      <input
                        type="file"
                        class="form-control"
                        :disabled="isFormDisabled"
                        v-on:change="onTalaoChange"
                        id="anexo"
                        accept="application/pdf,image/jpeg,image/png"
                      />
                    </div> -->

                    <!-- <div
                      class="col-12 col-md-12 mb-3"
                      v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                    >
                      <label for="" class="form-label">Observação</label>
                      <textarea
                        v-model="pagamento.Observacao"
                        cols="30"
                        rows="2"
                        placeholder="Observação"
                        class="form-control"
                        :disabled="isFormDisabled"
                      ></textarea>
                    </div> -->

                    <div
                      class="col-12 col-md-12"
                      v-if="pagamento.forma_pagamento != 'POR REFERÊNCIA'"
                    >
                      <!-- <p>
                        Talão(Obrigatório) | Formatos permitidos: PNG, PDF, JPG
                        | Tamanho máx. ficheiro: 2MB
                      </p> -->
                    </div>
                  </div>
                </form>
              </div>

              <div class="card-footer" >
                <button
                id="btn"
                  type="submit"
                  v-if="tabela.length != 0 || (fatura && fatura.ValorAPagar > 0 && !numero_fatura_nao_paga > 0)"
                  form="formulario_pagamento"
                  @click="registarFatura"
                  class="btn btn-primary"
                  style="float: right;"
                >
                  <i class="fa fa-paper-plane" aria-hidden="true" v-if="botao"></i> Salvar Pagamento
                </button>

                <button
                  type="submit"
                  id="btn"
                  v-if="tabela.length == 0 && numero_fatura_nao_paga > 0"
                  form="formulario_pagamento"
                  @click="registarPagamento"
                  class="btn btn-success"
                  style="float: right"
                >
                  <i class="fa fa-paper-plane" aria-hidden="true" v-if="botao"></i> Registar Pagamento
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>

<script>
import Swal from "sweetalert2";
import { sweetSuccess, sweetError } from "../../../components/Alert";
export default {
  props: ["forma_pagamentos"],
  data() {
    return {
      codigo_matricula: null,
      codigo_tipo_candidatura: null,
      ano_lectivo_id: null,
      isFormDisabled: true,

      anoLectivo: { Codigo: null },

      nome_estudante: null,
      bilheite_estudante: null,
      saldo_aluno: 0,
      saldo: 0,

      mostrar_dados_estudante: false,

      referencias_nao_pagas: [],
      numero_fatura_nao_paga: "",

      fatura: { ValorAPagar: 0, ValorEntregue: 0 },
      extenso: "",
      itens: [],
      pagamento: {valor_depositado: 0,},
      estudante: {},
      candidato: {},
      bancos: [],

      valor_pagamentos: 0,
      metadeValorPagar: 0,

      tipo_negociacao_id: 0,
      ativar_editar_valor: 0,
      anoLectivoFactura: "",

      total_adicionado: 0,
      valor_por_depositar: 0,

      talao_banco: null,
      opcoes: 1,

      ciclo_doutoramento:[],
      ciclo_mestrado:[],

      ultima_prestacao_antiga_paga: 0,
      prestacoes_por_ano: 0,
      todos_meses_pagos: 0,
      ultima_prestacao_paga: 0,
      anosLectivos: [],
      servicos: [],

      estudante_tipo1: {},
      estudante_tipo2: {},
      estudante_tipo3: {},
      estudante_tipo4: {},
      desconto_incentivo: 0,
      add_servico: {},
      info_desconto_ano_todo: "",
      desconto_finalista: 0,
      bolseiro: {},
      desconto_bolseiro: 0,
      desconto_anoTodo: 0,
      desconto_preinscricao: 0,
      descontoDaPreinscricao: 0,
      ano_lectivo_actual: 0,

















      selecionar: 1,

      bordero: "",
      erros: [],
      tabela: [],
      propina: {},
      ultimo_mes: "",
      mes_qtd: 1,
      meses: [],
      total_tabela: 0,
      fatura_id: "",
      referencia: "",
      totalFatura: "",
      meses_pagos: [],
      mes_seguinte: "",
      qtd_servico: 1,
      anos: [],
      referencias: [],
      ano_lectivo_sem_cadeiras_inscritas: 0,
      total: 0,
      preco: "",

      mesesNaoPagos: [],
      multa: 0,
      divida: 0,
      anoAtual: "",
      meses_temp: [],
      mes: {},
      adicionar_mes: true,
      dataAtual: "",
      desconto: 0,
      desconto_marco: 0,
      desconto_julho: 0,
      desconto_emerg: 0,
      mostrar_valor_fat: 0,
      adicionar1: false,
      adicionar2: false,
      remover1: false,
      remover2: false,
      falta_pagar: 0,
      desconto_outubro: 0,
      mes_seguinte_novo: "",
      ultimo_mes_novo: "",

      meses_temp_lista: [],
      mes_id: "",
      doencas: [],
      doenca_insert: [],
      pagarComSaldo: "",
      cadeiras: 0,
      preco_anterior: 0,
      loading: false,
      dias_uteis: [],
      mesAtual: "",
      diaAtual: "",
      anoAtual: "",
      botao: true,
      numero_fatura: "",
      transferencia_curso: "NAO",

      parametroMulta: {},
      mesesApagar: [],
      adicionar: false,
      //Navas variaveis
      classes: [],
      cadeirasMelhoria: [],
      cadeirasRecurso: [],
      registo: false,
      delete: false,
      inscricaoRecurso: [],
      inscricaoMelhoria: [],
      limite: {},
      cadeirasAtraso: [],
      cadeirasInseridas: [],
      editarCadeiras: [],
      cadeirasAeliminar: [],
      tamanho: 0,
      tamanhoAtraso: 0,
      tamanhoInseridas: 0,
      count_inseridas: 0,
      count_atraso: 0,
      count_total: 0,
      cad_atraso: [],
      cadeirasASelecionadas: [],
      tamanhoCA: 0,
      fezInscricao: "",
      cadeiraEstadoRecurso: 1,
      cadeiraEstadoMelhoria: 1,
      cadeirasInscritasRecurso: [],
      cadeirasInscritasMelhoria: [],
      recurso_selecionado: 0,
      melhoria_selecionada: 0,
      ultima_prestacao: {},
      primeira_prestacao: {},
      verifica_confirmacao_no_ano_corrente: [],
      desconto_excepcao_todos: 0,
      desconto_especial_nov21_jul22: 0,
      meses_bolsa: [],
      prazo_desconto_ano_todo: {},


    };
  },

  created(){

    this.getAnoLectivoActual();
  },

  watch: {
    fatura(val) {
      if (val) {
        if (this.fatura.descricao_factura == 5) {
          if (
            this.estudante.saldo >= 0 &&
            this.estudante.saldo < this.metadeValorPagar
          ) {
            this.pagamento.valor_depositado =
              this.metadeValorPagar - this.estudante.saldo;
          } else if (this.estudante.saldo >= this.metadeValorPagar) {
            this.pagamento.valor_depositado =
              this.estudante.saldo - this.metadeValorPagar;
          }
        } else if (
          this.estudante.saldo >= 0 &&
          this.estudante.saldo < this.fatura.ValorAPagar
        ) {
          this.pagamento.valor_depositado =
            this.fatura.ValorAPagar -
            this.fatura.ValorEntregue -
            this.estudante.saldo;
          this.valor_por_depositar =
            this.fatura.ValorAPagar -
            this.fatura.ValorEntregue -
            this.estudante.saldo;
        } else if (this.estudante.saldo >= this.fatura.ValorAPagar) {
          this.pagamento.valor_depositado =
            this.estudante.saldo -
            (this.fatura.ValorAPagar - this.fatura.ValorEntregue);
          this.valor_por_depositar =
            this.estudante.saldo -
            (this.fatura.ValorAPagar - this.fatura.ValorEntregue);
        }
      }
    },

    total_adicionado(val) {
      if (val) {
        if (this.estudante.saldo >= 0 && this.estudante.saldo < this.total_adicionado ) {
            this.pagamento.valor_depositado = this.total_adicionado - this.estudante.saldo;
            this.valor_por_depositar = this.total_adicionado - this.estudante.saldo;
            // this.estudante.saldo = 0;
        }else{
          if (this.estudante.saldo >= this.total_adicionado) {

            this.pagamento.valor_depositado = this.estudante.saldo - this.total_adicionado;
            this.valor_por_depositar = this.estudante.saldo - this.total_adicionado;
          } else {
            this.pagamento.valor_depositado = this.total_adicionado;
            this.valor_por_depositar = this.total_adicionado;
          }
        }
      }
    },
  },

  mounted(){
    // this.pegaPropina();
    // this.pegaUltimoMes();
  },

    methods: {
    onTalaoChange(e) {
      this.talao_banco = e.target.files[0];
    },

    registarPagamento: function () {

      if (this.pagamento.forma_pagamento == "POR REFERÊNCIA") {
        if (this.fatura.ValorAPagar < this.pagamento.valor_depositado) {
          //alert('Valor inválido! Informa um valor menor ou igual ao total a pagar.');
          return false;
        }
      }
      // this.pagamento.Codigo_PreInscricao = this.candidato.codigo_inscricao;
      const config = {
        headers: { "content-type": "multipart/form-data" },
      };

      let formData = new FormData();
      var pagamento = JSON.stringify(this.pagamento); //grande  solução.
      //var codigo_fatura=JSON.stringify(this.fatura_id);
      var codigo_fatura = JSON.stringify(this.numero_fatura_nao_paga);
      formData.append("pagamento", pagamento);
      formData.append("codigo_fatura", codigo_fatura);
      formData.append("talao_banco", this.talao_banco);
      // formData.append("talao_banco2", this.talao_banco2);
      formData.append("fonte", 1); // fonte de requisicao

      axios
        .post(
          "/pagamentos-estudantes/pagamento/diversos/create/" +
            this.codigo_matricula,
          formData,
          config,
          { referencia: this.numero_fatura_nao_paga }
        )
        .then((response) => {
            if(response.status === 200) {
                var fatura = this.numero_fatura_nao_paga;

                Swal.fire({
                    title: "Sucesso",
                    text: response.data.mensagem,
                    icon: "success",
                    confirmButtonColor: "#3d5476",
                    confirmButtonText: "Ok",
                    onClose: this.imprimirFatura(fatura)
                });
                this.codigo_matricula = null;
                this.nome_estudante = null,
                this.bilheite_estudante = null,
                this.pagamento.valor_depositado = 0,
                this.saldo_aluno = 0,
                this.pagamento = {};
                this.numero_fatura_nao_paga = "";
                document.getElementById("anexo").value = "";

                sweetSuccess(response.data.mensagem);
            } else if (response.status === 500) {
                sweetError("Falha de comunicação!");
            } else {
                sweetError(response.data);
            }
        })
        .catch((error) => {
          if (error.response.status == 422) {
            this.erros = error.response.data.errors;
            this.botao = true;
            document.getElementById("btn").disabled = false;
          }
        });
    },

    validar: function () {
      this.$validator.validate().then((valid) => {
        if (!valid) {
          sweetError(
            "Por favor preencha todos os campos obrigatórios com informações válidas!..."
          );
        } else {
          this.registarPagamento();
        }
      });
    },

    pesqisar_estudante(e) {
      e.preventDefault();
      this.$Progress.start();
      $(".table_estudantes").html("");
      axios
        .get(`/pesquisar-estudante?search=${this.codigo_matricula}`)
        .then((response) => {
          if (response.data.dados === null) {
            sweetError("Estudante Não Encontrado");
          } else {
            this.isFormDisabled = false;

            this.codigo_matricula = response.data.dados.Codigo;
            this.nome_estudante = response.data.dados.Nome_Completo;
            this.bilheite_estudante = response.data.dados.Bilhete_Identidade;
            this.codigo_tipo_candidatura = response.data.dados.codigo_tipo_candidatura;
            this.ano_lectivo_id = response.data.ano_lectivo_id;
            this.saldo_aluno = response.data.dados.saldo;

            (this.mostrar_dados_estudante = true),
            this.pegaAnoLectivo();
            this.getAnosLectivosEstudante();
            this.pegaPropina();
            this.pegaAluno();
            this.getTodasRefer();
            this.pegaSaldo();
            this.getCiclos();
            this.pegaServicos();
            this.pegarDescricaoBolseiro();
            this.pegaBolseiro();

            this.verificaConfirmacaoNoAnoLectivoCorrente();

            sweetSuccess("Estudante Encontrado com sucesso!");
          }
          this.$Progress.finish();
        })
        .catch((errors) => {
          this.$Progress.fail();
          sweetError("Estudante Não Encontrado!");
        });
    },

    // recuperar totas as factura do aluno não pagas
    getCiclos: function () {
      axios
        .get(`/ciclos`)
        .then((response) => {
            this.ciclo_mestrado = response.data.ciclo_mestrado;
            this.ciclo_doutoramento = response.data.ciclo_doutoramento;

        }).catch((error) => {
        });

    },

    getAnoLectivoActual: function () {
      axios
        .get(`/ano-lectivo-actual`)
        .then((response) => {
          this.ano_lectivo_actual = response.data.ano_lectivo_actual;

        })
        .catch((error) => {
        });

    },
    // recuperar totas as factura do aluno não pagas
    getTodasRefer: function () {
      axios
        .get(
          `/pagamentos-estudantes/todas-referencias-nao-pagas/${this.codigo_matricula}`
        )
        .then((response) => {
          this.referencias_nao_pagas = response.data;
          this.numero_fatura_nao_paga = response.data.codigo_fatura;
        })
        .catch((error) => {
        });
    },

    // recuperar todas as facturas
    faturaByReference: function () {
      if (this.numero_fatura_nao_paga == -1) {
        this.fatura = [];
        this.extenso = "";
        this.itens = [];
        this.valor_pagamentos = 0;
        //novo
        this.AllClean();
      } else {
        axios
          .get("/pagamentos-estudantes/fatura-by-reference", {
            params: { codigo_fatura: this.numero_fatura_nao_paga },
          })
          .then((response) => {
            this.fatura = response.data.fatura;
            this.extenso = response.data.extenso;
            this.itens = response.data.itens;
            this.valor_pagamentos =
              response.data.valor_depositado.valor_depositado;
            this.fatura.ValorAPagar = this.fatura.ValorAPagar
              ? this.fatura.ValorAPagar
              : 0;
            this.fatura.ValorEntregue = this.fatura.ValorEntregue
              ? this.fatura.ValorEntregue
              : 0;
            this.pagamento.valor_depositado = 0;
            this.metadeValorPagar = response.data.metadeValorPagar;
            this.tipo_negociacao_id = response.data.tipo_negociacao_id;
            this.ativar_editar_valor = response.data.disabled;
            this.anoLectivoFactura = this.fatura.ano_lectivo;
          })
          .catch((error) => {
            //console.log(error);
            //  toastr.warning('Houve uma falha ao carregar os dados!...');
          });
      }
    },

    pegaBancos: function () {
      axios
        .get("/banco-formaPagamento", {
          params: { forma_pagamento: this.pagamento.forma_pagamento },
        })
        .then((response) => {
          this.bancos = response.data;
        })
        .catch((error) => {});
    },

    pegaAluno: function () {
      this.loading = true;
      axios.get(`/aluno/${this.codigo_matricula}`).then((response) => {
        this.estudante = response.data;
        this.candidato = response.data;
      }).catch((error) => {});
    },

    pegaCandidato: function () {
      this.loading = true;
      axios
        .get(`/pagamentos-estudantes/candidato/${this.codigo_matricula}`)
        .then((response) => {
          this.candidato = response.data;
        })
        .catch((error) => {});
    },

    pegaSaldo: function () {
      axios
        .get(`/saldo/${this.codigo_matricula}`)
        .then((response) => {
          this.saldo = response.data;
        })
        .catch((error) => {});
    },

    pegaAnoLectivo: function () {
      axios
        .get(`/get-ano-lectivo/${this.codigo_matricula}`)
        .then((response) => {
          this.anoLectivo.Codigo = response.data;
        })
        .catch((error) => {});
    },

    pegaUltimoMes: function () {
      this.AllClean();
      axios
        .get(`/pagamentos-estudantes/ultimo-mes/${this.codigo_matricula}`, {
          params: { ano: this.anoLectivo.Codigo },
        })
        .then((response) => {
          if (+this.anoLectivo.Designacao <= 2019) {
            if (response.data.mes) {
              this.ultimo_mes = response.data.mes;
              this.ultima_prestacao_antiga_paga = response.data.prestacao;
            } else if (!response.data.mes) {
              this.ultimo_mes = "";
              this.ultima_prestacao_antiga_paga = 0;
            }
          } else {
            if (response.data.mes) {
                this.ultimo_mes_novo = response.data.mes;
                this.ultima_prestacao_paga = response.data.prestacao;

            } else if (!response.data.mes) {
              this.ultimo_mes_novo = "";
              this.ultima_prestacao_paga = 0;
            }
          }
        })
        .catch((error) => {});
    },

    async getPrestacoes() {
      await axios
        .get(
          `/pagamentos-estudantes/prestacoes-por-ano/${this.ano_lectivo_id}/${this.codigo_matricula}`
        )
        .then((response) => {
          this.meses_temp_lista = response.data.mes_temp;
          this.meses = response.data.mes_temp;
          this.prestacoes_por_ano = response.data.prestacoes_por_ano;
          this.todos_meses_pagos = response.data.todos_meses_pagos;

        })
        .catch((error) => {
        });
    },

    getAnosLectivosEstudante: function () {
      axios
        .get(`/pega-anos-lectivos-estudante/${this.codigo_matricula}`)
        .then((response) => {
          this.anosLectivos = response.data;
        })
        .catch((error) => {});
    },

    pegaServicos: function () {
      axios
        .get(
          `/pagamentos-estudantes/servicos/${this.anoLectivo.Codigo}/${this.codigo_matricula}`
        )
        .then((response) => {
          this.servicos = response.data;
        })
        .catch((error) => {});
    },

    pegarDescricaoBolseiro: function () {
      axios
        .get(`/estudante/pegar-descricao-bolseiro`)
        .then((response) => {
          this.estudante_tipo1 = response.data.descricao_tipo1;
          this.estudante_tipo2 = response.data.descricao_tipo2;
          this.estudante_tipo3 = response.data.descricao_tipo3;
          this.estudante_tipo4 = response.data.descricao_tipo4;
        })
        .catch((error) => {
          //console.log(error);
        });
    },

    aplicarDescontoAnoTodo() {
      if (
        this.opcoes == 1 &&
        this.anoLectivo.Codigo == this.ano_lectivo_actual
      ) {
        //angola
        if (
          this.mes_id == +this.ultima_prestacao.id &&
          this.tabela.length == +this.prestacoes_por_ano - 1 &&
          this.prazo_desconto_ano_todo
        ) {
          //if((new Date().toISOString().substr(0, 10)) >= "2021-10-30"){
          this.add_servico.Desconto = this.desconto_anoTodo; // não é aplicada a multa porque o desconto é sempre no ultimo mês de propina
          this.add_servico.Total =
            this.add_servico.Preco - this.add_servico.Desconto;
          this.info_desconto_ano_todo = "Desconto de 5% aplicado";
          //primeira_prestacao_sem_isencao
          //}
        }
      }
    },

    pegaPropina: function () {
    //   this.loading = true;
      this.getUltimaPrestacaoPorAnoLectivo();
      this.getPrimeiraPrestacaoPorAnoLectivo();
      this.getPrestacoes();

      this.$Progress.start();
      axios
        .get(`/pagamentos-estudantes/propina/${this.codigo_matricula}`, {
          params: { ano: this.anoLectivo.Codigo },
        })
        .then((response) => {
          this.propina = response.data.propina;
          this.add_servico = this.propina;
          this.totalFatura = response.data.fatura_id;
          this.meses_pagos = response.data.mesesPagos;
          this.saldo = response.data.saldo;
          this.desconto_preinscricao = response.data.desconto;
          this.mesesApagar = response.data.mesesApagar;
          this.dias_uteis = response.data.dias_uteis;
          this.mesAtual = response.data.mesAtual;
          this.diaAtual = response.data.diaAtual;
          this.anoAtual = response.data.anoAtual;
          this.ano_lectivo_sem_cadeiras_inscritas = response.data.ano_lectivo_sem_cadeiras_inscritas;
          this.transferencia_curso = response.data.transferencia_curso;
          this.parametroMulta = response.data.parametroMulta;
          this.desconto_incentivo= response.data.taxa_nov21_jul22;
          //desconto_especial_nov_fev2021
          this.desconto_especial_nov21_jul22 = this.propina.Preco * (response.data.taxa_nov21_jul22 / 100);

          this.desconto_excepcao_todos = (this.propina.Preco - this.propina.valor_anterior);

          //this.loading=false;
          this.pegaUltimoMes();
          this.pegaServicos();
          this.pegaBolseiro();
          this.pegaFinalista();


          this.cadeiras
          this.multa = this.propina.Preco * 0.1;
          this.desconto_marco =
            parseInt(this.propina.Preco) -
            (parseInt(this.propina.Preco / 2) +
              parseInt(this.propina.Preco / 2) * 0.6);
          //desconto de 5%
          this.desconto_julho =
            parseInt(this.propina.Preco) - parseInt(this.propina.Preco * 0.95);
          //desconto de 40%
          // this.propina.Preco*0.6-- o valor que paga
          this.desconto_emerg =
            parseInt(this.propina.Preco) - parseInt(this.propina.Preco * 0.6);
          // desconto de 10%
          this.desconto_outubro = this.propina.Preco * 0.1;

          this.desconto_finalista = this.propina.Preco * 0.5;

          this.desconto_anoTodo = this.propina.Preco * 0.5;

          this.descontoDaPreinscricao = this.propina.Preco * (this.desconto_preinscricao / 100);

          this.loading = false;

          this.$Progress.finish();
        })
        .catch((error) => {
          //console.log(error);
          //  toastr.warning('Houve uma falha ao carregar os dados!...');
          this.$Progress.fail();
        });
    },

    pegaBolseiro: function () {
      this.$Progress.start();
      axios
        .get(`/estudante/pega-bolseiro/${this.codigo_matricula}`, {
          params: { ano_lectivo: this.anoLectivo.Codigo },
        })
        .then((response) => {
          this.bolseiro = response.data;
          this.desconto_bolseiro =
            this.propina.Preco * (this.bolseiro.desconto / 100);
          this.mesesBolsa();
          this.$Progress.finish();
        })
        .catch((error) => {
          this.$Progress.fail();
        });
    },

    mesesBolsa: async function () {
      this.$Progress.start();
      await axios
        .get(`/estudante/prestacoes-por-bolsa-semestre`, {
          params: { ano_lectivo: this.anoLectivo.Codigo, codigo_matricula: this.codigo_matricula },
        })
        .then((response) => {
          this.meses_bolsa = response.data;

          this.$Progress.finish();
        })
        .catch((error) => {
          this.$Progress.fail();
        });
    },

    imprimirFatura: function (codigo_fatura) {
      window.open("/fatura/diversos/" + btoa(btoa(btoa(codigo_fatura))));
    },

    formatPrice(value) {
      let val = (value / 1).toFixed(2).replace(".", ",");
      return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },

    formatPriceNegociacao(value) {
      let val = (value / 1).toFixed(3).replace(".", ",");
      return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },


    validar: function () {

      // this.$validator.validate().then((valid) => {
      //   if (!valid) {
      //     // do stuff if not valid.
      //     //this.loading=false;
      //     Swal.fire({
      //       title: "Atenção!",
      //       text: "Por favor preencha todos os campos obrigatórios com informações válidas!...",
      //       icon: "warning",
      //       confirmButtonColor: "#3d5476",
      //     });
      //   } else {
      //     this.registarPagamento();
      //     //this.saldo=0;

      //     //}
      //   }
      // });
    },

    reloadPage() {
      window.location.reload();
    },


    async getUltimaPrestacaoPorAnoLectivo() {
     this.$Progress.start();
     await axios
     .get(`/pagamentos-estudantes/ultima-prestacao-por-ano/${this.anoLectivo.Codigo}/${this.codigo_matricula}`)
     .then((response) => {
        this.ultima_prestacao = response.data;
        this.$Progress.finish();
      })
      .catch((error) => {
        this.$Progress.fail();
      });
    },


    async getPrimeiraPrestacaoPorAnoLectivo() {

      this.$Progress.start();
      await axios
        .get(`/pagamentos-estudantes/primeira-prestacao-por-ano/${this.anoLectivo.Codigo}/${this.codigo_matricula}`)
        .then((response) => {
          this.primeira_prestacao = response.data.primeira_prestacao; //luanda
          this.prazo_desconto_ano_todo = response.data.prazo_desconto_ano_todo; //luanda
          this.$Progress.finish();
        })
        .catch((error) => {
          this.$Progress.fail();
        });
    },

    removerUltimo: function () {
      this.tabela.pop();
      this.decrementarAdicionado();
      //this.total_adicionado=0;
    },
    addOutrosServicos: function () {
      this.add_servico.Total = this.add_servico.Preco;
      this.add_servico.Multa = 0;
      this.add_servico.Desconto = 0;
      this.tabela.push({ ...this.add_servico, Mes: "#", mes_temp_id: null });
    },

    mesSeguinte: function (mes_ultimo) {
      if (mes_ultimo == "") {
        return this.meses[0];
      }
      for (var i = 0; i < this.meses.length - 1; i++) {
        if (mes_ultimo === this.meses[i]) {
          //alert(mes_ultimo + " ," + this.meses[i] )
          return this.meses[i + 1];
        }
      }
    },

    mesSeguinteNovo: function (mes_ultimo) {
      if (mes_ultimo == "") {
        var mes = this.meses_temp_lista.find(
          (mes) => mes.id == this.primeira_prestacao.id
        );

        return mes ? mes.designacao : "";
      }

      var mes_seguinte = "";
      this.meses_temp_lista.forEach((mes, key) => {
        if (mes_ultimo == mes.designacao) {
          mes_seguinte = this.meses_temp_lista[key + 1]
            ? this.meses_temp_lista[key + 1].designacao
            : "";

          if (((+this.meses_temp_lista[key + 1].prestacao) > +this.prestacoes_por_ano)) {
            Swal.fire({
              title: "Atenção",
              text: "A lista atingiu o limite máximo de itens por selecionar!",
              icon: "warning",
              confirmButtonColor: "#3d5476",
              confirmButtonText: "Ok",
              onClose: () => { },
            });
            return;
          }

        }
      });

      return mes_seguinte;
    },

    totalAdicionado: function () {
      var soma = 0;
      this.tabela.forEach((item) => {
        soma += parseFloat(item.Total);
      });
      this.total_adicionado = soma;
    },

    decrementarAdicionado: function () {
      var subtracao = 0;
      this.tabela.forEach((item) => {
        subtracao += parseFloat(item.Total);
      });
      this.total_adicionado = subtracao;
    },

    aplicarDescontoNov21Jul22() {
      this.add_servico.Desconto = this.desconto_especial_nov21_jul22;
    },

    aplicarDesconto: function () {
      if (this.add_servico.TipoServico == "Mensal") {
        if (this.mes.id == 1) {
          // this.add_servico.Desconto---valor a pagar  com desconto
          this.add_servico.Desconto = this.desconto_marco;
          this.add_servico.Total =
            parseInt(this.add_servico.Preco) -
            parseInt(this.add_servico.Desconto);
        } else if (this.mes.id == 2) {
          this.add_servico.Desconto = this.desconto_julho;
          this.add_servico.Total =
            parseInt(this.add_servico.Preco) -
            parseInt(this.add_servico.Desconto);
        } else if (this.mes.id == 3 || this.mes.id == 4 || this.mes.id == 5) {
          this.add_servico.Desconto = this.desconto_emerg;
          this.add_servico.Total =
            parseInt(this.add_servico.Preco) -
            parseInt(this.add_servico.Desconto);
        } else {
          this.add_servico.Desconto = 0;
          this.add_servico.Total = this.add_servico.Preco;
        }
      }
    },

    aplicarDescontoFinalista() {
      this.add_servico.Desconto = this.desconto_finalista;
    },

    aplicarDescontoBolseiro() {
      this.add_servico.Desconto = this.desconto_bolseiro;
    },

    aplicarDescontoPreinscricao() {
      this.add_servico.Desconto = this.descontoDaPreinscricao;
      //this.add_servico.Total=parseInt(this.add_servico.Preco)-(parseInt(this.add_servico.Desconto));
    },

    aplicarBolsaSemestral() {

      if (this.anoLectivo.ordem >= 17) {
        //alert(this.anoLectivo.Codigo+"  "+this.anoCorrente)
        this.meses_bolsa.forEach((item) => {
          if (this.mes_id == item.codigo) {
            if (item.desconto > 0 && item.desconto < 100) {
              this.add_servico.Desconto = this.add_servico.Preco * (item.desconto / 100);
            }
          }


        })
      } else {
        if (this.bolseiro &&
          this.bolseiro.desconto > 0 &&
          this.bolseiro.desconto < 100) {
          this.aplicarDescontoBolseiro() // bolsa anual

        }
      }
    },

    aplicarMultaAnoAtual() {
      var valor_com_desconto = 0;
      var multa_do_desconto = 0;
      var desconto = 0

      this.mesesApagar.forEach((item) => {

        if (this.mes_id == item.codigo) {

          valor_com_desconto = this.add_servico.Preco - this.add_servico.Desconto;
          multa_do_desconto = valor_com_desconto * (item.taxa / 100);
          this.add_servico.Multa = multa_do_desconto;

          if (this.cadeiras >= 0 && this.cadeiras <= 3) {

            multa_do_desconto = valor_com_desconto * (item.taxa / 100);
            this.add_servico.Multa = multa_do_desconto;
          }

          this.add_servico.Total = this.add_servico.Preco - this.add_servico.Desconto +  parseFloat(this.add_servico.Multa);
        }
      });
    },

    adicionarMeses: function () {
      if (+this.anoLectivo.Designacao <= 2019) {
        if ((this.opcoes == 1)) {
          if ((+this.ultima_prestacao_antiga_paga >= this.prestacoes_por_ano) || (+this.tabela.length + this.todos_meses_pagos >= this.meses.length)) {
            Swal.fire({
              title: "Atenção",
              text: "A lista atingiu o limite máximo de prestações por selecionar!",
              icon: "warning",
              confirmButtonColor: "#3d5476",
              confirmButtonText: "Ok",
              onClose: () => { },
            });
            return;
          } else {
            this.addAnosAnteriores();
          }
        }
      } else {
        if ((this.opcoes == 1)) {
          if ((+this.ultima_prestacao_paga >= +this.prestacoes_por_ano) || (+this.tabela.length + this.todos_meses_pagos >= +this.meses_temp_lista.length)) {
            Swal.fire({
              title: "Atenção",
              text: "A lista atingiu o limite máximo de prestações por selecionar!",
              icon: "warning",
              confirmButtonColor: "#3d5476",
              confirmButtonText: "Ok",
              onClose: () => { },
            });
            return;
          }
        }

        if ((this.opcoes == 1) && (this.anoLectivo.Codigo == this.ano_lectivo_actual) && this.estudante.tipo_candidatura == 1) {
          if ((this.verifica_confirmacao_no_ano_corrente.length > 0)) {
            this.add();
          } else {
            Swal.fire({
              title: "Atenção prezado(a) estudante",
              text: "Para fazer o pagamento de propina deve primeiro inscrever-se nas unidades curriculares para este ano lectivo",
              icon: "warning",
              confirmButtonColor: "#3d5476",
              confirmButtonText: "Ok",
              onClose: () => { },
            });
            return 0;
          }
        } else if (this.estudante.tipo_candidatura == 1 && (this.opcoes == 1 || this.opcoes == 2) && (this.anoLectivo.Codigo != this.ano_lectivo_actual)) {
          Swal.fire({
            title: "Atenção prezado(a) estudante",
            text: "Não é possivel efectuar pagamentos para o ano lectivo escolhido! Faça a negociação de dívidas",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => { },
          });
          return 0;
        } else {
          this.add();
        }
      }
    },

    aplicarMesId: function (mes_temp) {

      var mes = this.meses_temp_lista.find((mes) => mes.designacao == mes_temp);
      this.mes_id = mes ? mes.id : "";
    },

    add: function () {
      if (this.add_servico.TipoServico == "Mensal") {
        this.add_servico.Multa = 0;
        this.add_servico.Desconto = 0;

        //Desconto geral desconto = valor_total_com_reajuste - valor_total_sem_reajuste até dia 30.10.2021, desconto_excepcao_todos
        this.aplicarDescontoNov21Jul22();
        if (this.cadeiras >= 0 && this.cadeiras <= 3) {
          this.aplicarDescontoFinalista();
        }
        if (this.desconto_preinscricao > 0) {
          this.aplicarDescontoPreinscricao();
          //}
        }
        this.add_servico.Total =
          this.add_servico.Preco -
          this.add_servico.Desconto +
          parseInt(this.add_servico.Multa);


        for (var key = 0; key < this.mes_qtd; key++) {
          if ( this.mes_seguinte_novo != this.ultima_prestacao.designacao &&
            this.ultimo_mes_novo != this.ultima_prestacao.designacao
          ) {
            if (this.tabela.length == 0) {
              var mes = this.ultimo_mes_novo;

              this.mes_seguinte_novo = this.mesSeguinteNovo(mes);

              if (this.add_servico.TipoServico == "Mensal") {

                this.aplicarMesId(this.mes_seguinte_novo);

                this.aplicarBolsaSemestral()

                if (!((+this.anoLectivo.Designacao) <= 2019)) {
                  this.aplicarMultaAnoAtual();
                }

                this.tabela.push({
                  ...this.add_servico,
                  Mes: this.mes_seguinte_novo,
                  mes_temp_id: this.mes_id,
                });
              }
            } else {
              this.mes_seguinte_novo = this.mesSeguinteNovo(
                this.mes_seguinte_novo
              );

              if (this.add_servico.TipoServico == "Mensal") {
                this.aplicarMesId(this.mes_seguinte_novo);

                this.aplicarBolsaSemestral()

                if (this.desconto_especial_nov21_jul22 == 0) {

                  if (this.cadeiras > 3 && !this.bolseiro) {
                    this.aplicarDescontoAnoTodo();
                  }
                }
                if (!((+this.anoLectivo.Designacao) <= 2019)) {
                  this.aplicarMultaAnoAtual();
                }
                this.tabela.push({
                  ...this.add_servico,
                  Mes: this.mes_seguinte_novo,
                  mes_temp_id: this.mes_id,
                });
              }
            }
          }
        }
      } else {
        this.addOutrosServicos();
      }
      this.totalAdicionado();
    },

    addAnosAnteriores: function () {
      if (
        this.add_servico.TipoServico == "Mensal" &&
        +this.anoLectivo.Designacao <= 2019
      ) {
        if (+this.anoLectivo.Designacao <= 2019) {
          this.add_servico.Desconto = 0;
          this.add_servico.Multa = this.multa;
          var valor_com_desconto1 = 0;
          var multa_do_desconto1 = 0;
          if (
            this.bolseiro &&
            this.bolseiro.desconto != 100 &&
            this.bolseiro.desconto != 0
          ) {
            this.aplicarDescontoBolseiro();
          }

          if (this.desconto_preinscricao > 0) {
            this.aplicarDescontoPreinscricao();
            valor_com_desconto1 = this.add_servico.Preco - this.add_servico.Desconto;
            this.add_servico.Multa = valor_com_desconto1 * 0.1;


            // foi reimplementado(estava comentado) em 07/01/22 por orientacao do Gaspar passada pelo Serqueira

          }
          this.add_servico.Total =
            parseFloat(this.add_servico.Multa) +
            parseFloat(this.add_servico.Preco) -
            parseFloat(this.add_servico.Desconto);
        } else {
          this.add_servico.Multa = 0;
          this.add_servico.Total = this.add_servico.Preco;
        }
        for (var key = 0; key < this.mes_qtd; key++) {
          if (this.mes_seguinte != "DEZ" && this.ultimo_mes != "DEZ") {
            if (this.tabela.length == 0) {
              var mes = this.ultimo_mes;
              this.mes_seguinte = this.mesSeguinte(mes);
              ///alert(this.mes_seguinte+" , "+this.ultimo_mes);
              if (this.add_servico.TipoServico == "Mensal") {
                this.tabela.push({
                  ...this.add_servico,
                  Mes: this.mes_seguinte,
                  mes_temp_id: null,
                });
              }
            } else {
              this.mes_seguinte = this.mesSeguinte(this.mes_seguinte);

              if (this.add_servico.TipoServico == "Mensal") {
                //this.add_servico.Mes=this.meses[key];

                this.tabela.push({
                  ...this.add_servico,
                  Mes: this.mes_seguinte,
                  mes_temp_id: null,
                });
              }
            }
          }
        }
      } else {
        this.addOutrosServicos();
      }
      this.totalAdicionado();
    },

    AllClean: function (key) {
      this.$Progress.start();
      this.tabela.splice(key);
      this.total_adicionado = 0;
      this.$Progress.finish();
    },

    remove: function (key) {
      //this.meus_servicos.splice(index,1)
      this.$delete(this.tabela, key);
    },

    pegaFinalista: function () {
      //alert(this.anoLectivo.Codigo)
      this.$Progress.start();
      axios
        .get(`/estudante/pega-finalista/${this.codigo_matricula}`, { params: { ano_lectivo: this.anoLectivo.Codigo } })
        .then((response) => {
          this.cadeiras = response.data;
          this.$Progress.finish();
        })
        .catch((error) => {
          this.$Progress.fail();
        });
    },

    getRefer: function () {
      this.$Progress.start();

      axios
        .get(`/estudante/referencias-nao-pagas/${this.codigo_matricula}`)
        .then((response) => {
          this.referencias = response.data;
          this.numero_fatura = this.referencias.codigo_fatura;
          this.$Progress.finish();
        })
        .catch((error) => {
          this.$Progress.fail();
        });
    },

    onTalaoChange(e) {
      //console.log(e.target.files[0]);
      this.talao_banco = e.target.files[0];
    },

    onTalao2Change(e) {
      //console.log(e.target.files[0]);
      this.talao_banco2 = e.target.files[0];
    },

    activarForm: function () {
      $("select").formSelect();
      $(".tabs").tabs();
      $(".modal").modal();
      $(".sidenav").sidenav();
      $(".collapsible").collapsible();
    },

    registarFatura: function () {
      if (
        (this.pagamento.valor_depositado == null ||
          this.pagamento.valor_depositado == 0) &&
        Math.ceil(this.estudante.saldo) < Math.ceil(this.total_adicionado)
      ) {
        Swal.fire({
          title: "Dados Incorrectos",
          text: "Por favor preencha o campo: Valor a Depositar",
          icon: "error",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
        });

        document.getElementById("btn").disabled = false;
      } else if (
        this.pagamento.valor_depositado > 0 &&
        this.estudante.saldo > 0 &&
        Math.ceil(this.pagamento.valor_depositado + this.estudante.saldo) < this.total_adicionado
      ) {

        Swal.fire({
          title: "Dados Incorrectos",
          text: "O Valor entregue é Inferior ao Valor a Pagar =" + this.total_adicionado,
          icon: "error",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
        });

        document.getElementById("btn").disabled = false;
      } else if (this.valor_por_depositar != null && (this.opcoes == 1 || this.opcoes == 2) && (this.pagamento.valor_depositado < this.valor_por_depositar)) {
        Swal.fire({
          title: "Dados Incorrectos",
          text: "O Valor entregue não corresponde ao valor da factura, deve ser igual ou maior a " + (this.valor_por_depositar) + ' kzs',
          icon: "error",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok"
        });
        document.getElementById("btn").disabled = false;
        return false;
      }
      else {
        this.botao = false;
        document.getElementById("btn").disabled = true;
        //dados do pagamento
        const config = {
          headers: { "content-type": "multipart/form-data" },
        };

        let formData = new FormData();
        var fatura_item = JSON.stringify(this.tabela); //grande  solução.
        var anoLectivo = JSON.stringify(this.anoLectivo.Codigo);
        var parametroSaldo = JSON.stringify(this.pagarComSaldo);
        var pagamento = JSON.stringify(this.pagamento); //grande  solução.
        var referencia = JSON.stringify(this.referencia);
        formData.append("fatura_item", fatura_item);
        formData.append("anoLectivo", anoLectivo);
        formData.append("parametroSaldo", parametroSaldo);
        formData.append("pagamento", pagamento);
        formData.append("referencia", referencia);
        formData.append("fonte", 2);// fonte de requisicao

        axios
          .post("/pagamentos-estudantes/fatura/diversos/create/" +
            this.codigo_matricula,
            formData,
            config,
          )
          .then((response) => {
            if (response.status === 200) {
                document.getElementById("btn").disabled = false;
                this.fatura_id = response.data.codigo_fatura;
                this.total_adicionado = 0;

                this.botao = true;
                var fatura_ref = this.fatura_id;

                Swal.fire({
                    title: "Sucesso",
                    text: response.data.message,
                    icon: "success",
                    confirmButtonColor: "#3d5476",
                    confirmButtonText: "Ok",
                    // onClose: () => {
                    //     this.imprimirFatura(fatura_ref);
                    // },
                    onClose: this.imprimirFatura(fatura_ref)
                });
                this.tabela = [];
                this.codigo_matricula = null;
                this.nome_estudante = null,
                this.bilheite_estudante = null,
                this.pagamento.valor_depositado = 0,
                this.saldo_aluno = 0,
                this.codigo_matricula = null;
                this.saldo = 0;
            } else {
              Swal.fire({
                icon: "info",
                title: "Atenção...",
                text: response.data.message,
              });
              this.botao = true;
            }
          })
          .catch((error) => {
            if (error.response.status === 422) {
              this.erros = error.response.data.errors;
              this.botao = true
              document.getElementById("btn").disabled = false;
            }
          });
      }
    },

    verificaConfirmacaoNoAnoLectivoCorrente: function () {
      this.$Progress.start();
      axios.get(`/estudante/verifica-confirmacao-no-ano-corrente/${this.codigo_matricula}`)
      .then((response) => {
        this.verifica_confirmacao_no_ano_corrente = response.data;
        this.$Progress.finish();
      })
        .catch((error) => {
        this.$Progress.fail();
        });
    },
  },
};
</script>


